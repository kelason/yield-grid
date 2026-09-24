import { useWebSocket } from './useWebSocket'

export function useForumWebSocket() {
  const { echo } = useWebSocket()

  const listenToThread = (threadId, callbacks) => {
    if (!echo) return null

    return echo
      .private(`thread.${threadId}`)
      .listen('NewReplyPosted', (e) => {
        if (callbacks.onReply) callbacks.onReply(e)
      })
      .listen('.NewReplyPosted', (e) => {
        if (callbacks.onReply) callbacks.onReply(e)
      })
      .listen('ThreadVoteUpdated', (e) => {
        if (callbacks.onVote) callbacks.onVote(e)
      })
      .listen('.ThreadVoteUpdated', (e) => {
        if (callbacks.onVote) callbacks.onVote(e)
      })
  }

  const leaveThread = (threadId) => {
    if (echo) {
      echo.leave(`thread.${threadId}`)
    }
  }

  return {
    listenToThread,
    leaveThread,
  }
}
