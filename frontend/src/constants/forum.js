export const FORUM_CONSTANTS = {
  TITLE_MIN_LENGTH: 10,
  TITLE_MAX_LENGTH: 100,
  BODY_MIN_LENGTH: 20,
  BODY_MAX_LENGTH: 5000,
  REPLY_MIN_LENGTH: 5,
  REPLY_MAX_LENGTH: 5000,
  MAX_TAGS_PER_THREAD: 5,
  THREADS_PER_PAGE: 15,
  REPLIES_PER_PAGE: 20,
}

export const VOTE_VALUES = {
  UPVOTE: 1,
  DOWNVOTE: -1,
}

export const THREAD_SORT_OPTIONS = [
  { value: 'latest', label: 'Latest Activity' },
  { value: 'most_voted', label: 'Most Voted' },
  { value: 'most_replied', label: 'Most Replied' },
  { value: 'unanswered', label: 'Unanswered' },
]
