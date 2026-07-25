export const getQuizNavigationState = (activeIndex, totalQuestions) => {
  const lastIndex = Math.max(Number(totalQuestions) - 1, 0);
  const normalizedIndex = Math.min(
    Math.max(Number(activeIndex) || 0, 0),
    lastIndex
  );
  const isFirst = normalizedIndex === 0;
  const isLast = normalizedIndex === lastIndex;

  return {
    isFirst,
    isLast,
    previousDisabled: isFirst,
    showValidate: !isLast,
    showSubmit: isLast,
  };
};
