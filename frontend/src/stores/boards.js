import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { boardsApi } from '../api/boards'

export const useBoardsStore = defineStore('boards', () => {
  const boards = ref([])
  const currentBoard = ref(null)
  const loading = ref(false)

  const sortedLists = computed(() => {
    if (!currentBoard.value?.lists) return []
    return [...currentBoard.value.lists]
      .filter(l => !l.is_archived)
      .sort((a, b) => a.position - b.position)
  })

  async function fetchBoards() {
    loading.value = true
    try {
      const res = await boardsApi.list()
      boards.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchBoard(id) {
    loading.value = true
    try {
      const res = await boardsApi.show(id)
      currentBoard.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function createBoard(data) {
    const res = await boardsApi.create(data)
    boards.value.push(res.data.data)
    return res.data.data
  }

  async function updateBoard(id, data) {
    const res = await boardsApi.update(id, data)
    const idx = boards.value.findIndex(b => b.id === id)
    if (idx !== -1) boards.value[idx] = res.data.data
    if (currentBoard.value?.id === id) {
      Object.assign(currentBoard.value, res.data.data)
    }
    return res.data.data
  }

  async function toggleStar(id) {
    const board = boards.value.find(b => b.id === id)
    if (!board) return
    return updateBoard(id, { is_starred: !board.is_starred })
  }

  async function addList(boardId, name) {
    const res = await boardsApi.createList(boardId, { name })
    if (currentBoard.value?.id === boardId) {
      currentBoard.value.lists.push(res.data.data)
    }
    return res.data.data
  }

  async function renameList(boardId, listId, name) {
    const res = await boardsApi.updateList(boardId, listId, { name })
    if (currentBoard.value?.id === boardId) {
      const list = currentBoard.value.lists.find(l => l.id === listId)
      if (list) list.name = res.data.data.name
    }
  }

  async function archiveList(boardId, listId) {
    await boardsApi.archiveList(boardId, listId)
    if (currentBoard.value?.id === boardId) {
      const list = currentBoard.value.lists.find(l => l.id === listId)
      if (list) list.is_archived = true
    }
  }

  async function reorderLists(boardId, positions) {
    await boardsApi.reorderLists(boardId, positions)
  }

  async function addCard(boardId, listId, data) {
    const res = await boardsApi.createCard(boardId, listId, data)
    if (currentBoard.value?.id === boardId) {
      const list = currentBoard.value.lists.find(l => l.id === listId)
      if (list) {
        if (!list.cards) list.cards = []
        list.cards.push(res.data.data)
      }
    }
    return res.data.data
  }

  async function moveCard(cardId, listId, position) {
    const res = await boardsApi.moveCard(cardId, { list_id: listId, position })
    return res.data.data
  }

  async function reorderCards(boardId, listId, positions) {
    await boardsApi.reorderCards(boardId, listId, positions)
  }

  function clearBoard() {
    currentBoard.value = null
  }

  return {
    boards, currentBoard, loading, sortedLists,
    fetchBoards, fetchBoard, createBoard, updateBoard, toggleStar,
    addList, renameList, archiveList, reorderLists,
    addCard, moveCard, reorderCards, clearBoard,
  }
})
