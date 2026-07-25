export const MOBILE_COURSE_INDEX_QUERY = '(max-width: 767px)';

export const shouldScrollToCourseTopics = (isMobile, hasHeading) =>
  Boolean(isMobile && hasHeading);
