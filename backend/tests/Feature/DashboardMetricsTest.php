<?php

namespace Tests\Feature;

use App\Domain\Board\Models\Board;
use App\Domain\Board\Models\BoardList;
use App\Domain\Board\Models\Card;
use App\Domain\Board\Services\BoardService;
use App\Domain\Plan\Models\Plan;
use App\Domain\User\Models\User;
use App\Domain\Workspace\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Workspace $workspace;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create plan
        $plan = Plan::create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Plano gratuito',
            'max_members' => 5,
            'max_boards' => 10,
            'max_storage_mb' => 100,
            'max_labels' => 6,
            'max_attachment_size_mb' => 5,
            'features' => [],
            'price_monthly' => 0,
            'price_annual' => 0,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // Create user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@orbita.com',
            'password' => 'password',
        ]);

        // Create workspace with agency persona
        $this->workspace = Workspace::create([
            'name' => 'Test Agency',
            'slug' => 'test-agency-' . Str::random(6),
            'plan_id' => $plan->id,
            'owner_id' => $this->user->id,
            'is_active' => true,
            'persona' => 'agency',
        ]);

        // Attach user as workspace member
        $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);

        // Get JWT token
        $this->token = auth()->login($this->user);
    }

    /**
     * Helper: create a board with lists via BoardService
     */
    private function createBoardWithTemplate(string $template, string $name, ?string $clientName = null): Board
    {
        $boardService = new BoardService();
        return $boardService->createBoard([
            'name' => $name,
            'template' => $template,
            'client_name' => $clientName,
        ], $this->user, $this->workspace);
    }

    /**
     * Helper: create a card in a specific list
     */
    private function createCard(BoardList $list, string $title, ?string $dueDate = null): Card
    {
        $maxPosition = Card::where('list_id', $list->id)->max('position') ?? 0;

        return Card::create([
            'list_id' => $list->id,
            'board_id' => $list->board_id,
            'title' => $title,
            'position' => $maxPosition + 1000,
            'due_date' => $dueDate,
            'is_archived' => false,
            'created_by_id' => $this->user->id,
        ]);
    }

    /**
     * Helper: get authenticated request with workspace header
     */
    private function apiGet(string $uri)
    {
        return $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'X-Workspace-Id' => $this->workspace->id,
        ])->getJson("/api/v1/{$uri}");
    }

    // ─── TEST CASES ────────────────────────────────────────

    public function test_dashboard_returns_200_with_valid_structure(): void
    {
        // Create a board with template
        $this->createBoardWithTemplate('agency_pipeline', 'Board Test');

        $response = $this->apiGet('dashboard/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'overview' => [
                        'total_boards',
                        'total_cards',
                        'cards_completed',
                        'cards_overdue',
                        'active_members',
                    ],
                    'cards_by_status',
                    'boards_summary',
                    'completion_trend',
                ],
            ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->withHeaders([
            'X-Workspace-Id' => $this->workspace->id,
        ])->getJson('/api/v1/dashboard/metrics');

        $response->assertStatus(401);
    }

    public function test_dashboard_requires_workspace_header(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v1/dashboard/metrics');

        $response->assertStatus(400);
    }

    public function test_empty_workspace_returns_zero_metrics(): void
    {
        $response = $this->apiGet('dashboard/metrics');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'overview' => [
                        'total_boards' => 0,
                        'total_cards' => 0,
                        'cards_completed' => 0,
                        'cards_overdue' => 0,
                        'active_members' => 1,
                    ],
                    'cards_by_status' => [],
                    'boards_summary' => [],
                ],
            ]);
    }

    public function test_overview_counts_boards_and_cards_correctly(): void
    {
        // Create agency board (6 lists: Briefing, Produção, Revisão Interna, Revisão Cliente, Aprovado, Entregue)
        $board = $this->createBoardWithTemplate('agency_pipeline', 'Pipeline Acme', 'Acme Corp');
        $lists = $board->lists()->orderBy('position')->get();

        // Create cards across lists
        $this->createCard($lists[0], 'Briefing Card 1');      // Briefing
        $this->createCard($lists[0], 'Briefing Card 2');      // Briefing
        $this->createCard($lists[1], 'Production Card 1');    // Produção
        $this->createCard($lists[2], 'Internal Review 1');    // Revisão Interna
        $this->createCard($lists[5], 'Delivered Card 1');     // Entregue (last = completed)
        $this->createCard($lists[5], 'Delivered Card 2');     // Entregue (last = completed)

        $response = $this->apiGet('dashboard/metrics');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(1, $data['overview']['total_boards']);
        $this->assertEquals(6, $data['overview']['total_cards']);
        $this->assertEquals(2, $data['overview']['cards_completed']); // 2 in last list
        $this->assertEquals(1, $data['overview']['active_members']);
    }

    public function test_overdue_cards_detected_correctly(): void
    {
        $board = $this->createBoardWithTemplate('design_pipeline', 'Design Board');
        $lists = $board->lists()->orderBy('position')->get();

        // Card overdue (due_date in past, NOT in last list)
        $this->createCard($lists[0], 'Overdue Task', '2025-01-01');

        // Card overdue in middle list
        $this->createCard($lists[2], 'Overdue Review', '2025-06-01');

        // Card with past due but IN last list (completed, NOT overdue)
        $this->createCard($lists[4], 'Completed Late', '2025-03-01');

        // Card without due_date (NOT overdue)
        $this->createCard($lists[1], 'No Due Date');

        // Card with future due_date (NOT overdue)
        $this->createCard($lists[1], 'Future Due', '2027-12-31');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertEquals(2, $data['overview']['cards_overdue']);  // Only 2 overdue
        $this->assertEquals(1, $data['overview']['cards_completed']); // 1 in last list
        $this->assertEquals(5, $data['overview']['total_cards']);
    }

    public function test_boards_summary_shows_progress_per_board(): void
    {
        // Board 1: 4 cards, 1 completed = 25%
        $board1 = $this->createBoardWithTemplate('freelancer_simple', 'Projetos', null);
        $lists1 = $board1->lists()->orderBy('position')->get();
        $this->createCard($lists1[0], 'Task A');
        $this->createCard($lists1[1], 'Task B');
        $this->createCard($lists1[2], 'Task C');
        $this->createCard($lists1[3], 'Task Done'); // Concluído = last list

        // Board 2: 2 cards, 2 completed = 100%
        $board2 = $this->createBoardWithTemplate('client_portal', 'Pedidos', null);
        $lists2 = $board2->lists()->orderBy('position')->get();
        $this->createCard($lists2[3], 'Approved 1'); // Aprovado = last list
        $this->createCard($lists2[3], 'Approved 2');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertCount(2, $data['boards_summary']);

        // Find each board in summary
        $summary1 = collect($data['boards_summary'])->firstWhere('id', $board1->id);
        $summary2 = collect($data['boards_summary'])->firstWhere('id', $board2->id);

        $this->assertEquals(4, $summary1['total_cards']);
        $this->assertEquals(1, $summary1['completed']);
        $this->assertEquals(25, $summary1['progress']);

        $this->assertEquals(2, $summary2['total_cards']);
        $this->assertEquals(2, $summary2['completed']);
        $this->assertEquals(100, $summary2['progress']);
    }

    public function test_agency_board_includes_client_name_in_summary(): void
    {
        $board = $this->createBoardWithTemplate('agency_pipeline', 'Pipeline Acme', 'Acme Corp');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $summary = $data['boards_summary'][0];
        $this->assertEquals('Acme Corp', $summary['client_name']);
        $this->assertEquals('Pipeline Acme', $summary['name']);
    }

    public function test_cards_by_status_groups_by_list_name(): void
    {
        $board = $this->createBoardWithTemplate('design_pipeline', 'Design Board');
        $lists = $board->lists()->orderBy('position')->get();

        // 3 cards in "Briefing", 2 in "Em Criação", 1 in "Entregue"
        $this->createCard($lists[0], 'Brief 1');
        $this->createCard($lists[0], 'Brief 2');
        $this->createCard($lists[0], 'Brief 3');
        $this->createCard($lists[1], 'Create 1');
        $this->createCard($lists[1], 'Create 2');
        $this->createCard($lists[4], 'Done 1');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $statusMap = collect($data['cards_by_status'])->pluck('count', 'list_name');

        $this->assertEquals(3, $statusMap['Briefing']);
        $this->assertEquals(2, $statusMap['Em Criação']);
        $this->assertEquals(1, $statusMap['Entregue']);
    }

    public function test_archived_boards_excluded_from_metrics(): void
    {
        // Active board
        $activeBoard = $this->createBoardWithTemplate('freelancer_simple', 'Active');
        $activeLists = $activeBoard->lists()->orderBy('position')->get();
        $this->createCard($activeLists[0], 'Active Task');

        // Archived board
        $archivedBoard = $this->createBoardWithTemplate('freelancer_simple', 'Archived');
        $archivedBoard->update(['is_archived' => true]);
        $archivedLists = $archivedBoard->lists()->orderBy('position')->get();
        $this->createCard($archivedLists[0], 'Archived Task');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertEquals(1, $data['overview']['total_boards']);
        $this->assertEquals(1, $data['overview']['total_cards']);
        $this->assertCount(1, $data['boards_summary']);
    }

    public function test_archived_cards_excluded_from_counts(): void
    {
        $board = $this->createBoardWithTemplate('freelancer_simple', 'Board');
        $lists = $board->lists()->orderBy('position')->get();

        $this->createCard($lists[0], 'Active Card');
        $archivedCard = $this->createCard($lists[0], 'Archived Card');
        $archivedCard->update(['is_archived' => true]);

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertEquals(1, $data['overview']['total_cards']);
    }

    public function test_completion_trend_returns_4_weeks(): void
    {
        $board = $this->createBoardWithTemplate('freelancer_simple', 'Board');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertCount(4, $data['completion_trend']);
        foreach ($data['completion_trend'] as $week) {
            $this->assertArrayHasKey('week', $week);
            $this->assertArrayHasKey('completed', $week);
        }
    }

    public function test_multiple_boards_aggregate_correctly(): void
    {
        // Board 1: agency_pipeline with client
        $board1 = $this->createBoardWithTemplate('agency_pipeline', 'Client A Pipeline', 'Client A');
        $lists1 = $board1->lists()->orderBy('position')->get();
        $this->createCard($lists1[0], 'B1 Task 1');
        $this->createCard($lists1[5], 'B1 Done'); // last list = completed

        // Board 2: agency_pipeline with different client
        $board2 = $this->createBoardWithTemplate('agency_pipeline', 'Client B Pipeline', 'Client B');
        $lists2 = $board2->lists()->orderBy('position')->get();
        $this->createCard($lists2[1], 'B2 Task 1');
        $this->createCard($lists2[1], 'B2 Task 2');
        $this->createCard($lists2[5], 'B2 Done 1'); // completed
        $this->createCard($lists2[5], 'B2 Done 2'); // completed

        // Board 3: no client
        $board3 = $this->createBoardWithTemplate('agency_pipeline', 'Internal', null);
        $lists3 = $board3->lists()->orderBy('position')->get();
        $this->createCard($lists3[0], 'B3 Task 1');

        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertEquals(3, $data['overview']['total_boards']);
        $this->assertEquals(7, $data['overview']['total_cards']);      // 2 + 4 + 1
        $this->assertEquals(3, $data['overview']['cards_completed']);  // 1 + 2 + 0

        // Verify client_names in summary
        $summaries = collect($data['boards_summary']);
        $this->assertEquals('Client A', $summaries->firstWhere('id', $board1->id)['client_name']);
        $this->assertEquals('Client B', $summaries->firstWhere('id', $board2->id)['client_name']);
        $this->assertNull($summaries->firstWhere('id', $board3->id)['client_name']);
    }

    public function test_all_persona_templates_create_boards_successfully(): void
    {
        $personas = [
            'agency' => ['template' => 'agency_pipeline', 'lists' => 6],
            'studio' => ['template' => 'design_pipeline', 'lists' => 5],
            'freelancer' => ['template' => 'freelancer_simple', 'lists' => 4],
            'client' => ['template' => 'client_portal', 'lists' => 4],
        ];

        foreach ($personas as $persona => $config) {
            $board = $this->createBoardWithTemplate($config['template'], "Board {$persona}");
            $listCount = $board->lists()->count();

            $this->assertEquals(
                $config['lists'],
                $listCount,
                "Persona '{$persona}' com template '{$config['template']}' deveria ter {$config['lists']} listas, mas tem {$listCount}"
            );
        }

        // All 4 boards should appear in dashboard
        $response = $this->apiGet('dashboard/metrics');
        $data = $response->json('data');

        $this->assertEquals(4, $data['overview']['total_boards']);
    }
}
