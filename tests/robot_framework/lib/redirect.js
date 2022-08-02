async function clickAndGetResponseHeaders(page, locator, expectResponseURL) {
    const [response] = await Promise.all([
        page.waitForResponse("**/" + expectResponseURL),
        page.click(locator)
    ])
    return response.headers();
  }
  exports.__esModule = true;
  exports.clickAndGetResponseHeaders = clickAndGetResponseHeaders;
  