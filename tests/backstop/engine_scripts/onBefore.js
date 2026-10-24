module.exports = async (page) => {
  const context = page.context();
  if (context && context.clearCookies) {
    await context.clearCookies();
  }
};
