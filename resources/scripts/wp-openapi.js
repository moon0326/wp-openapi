/**
 * External dependencies
 */
import { render } from "@wordpress/element";
import { API } from "@stoplight/elements";
import "@stoplight/elements/styles.min.css";

const elementsAppContainer = document.getElementById("elements-app");

const { fetch: originalFetch } = window;

window.fetch = async (...args) => {
  let [resource, config] = args;

  if (
    resource.indexOf("wp-json") !== false &&
    config?.headers &&
    config.headers["X-WP-Nonce"] === undefined
  ) {
    config.headers["X-WP-Nonce"] = wpOpenApi.nonce;
  }

  const response = await originalFetch(resource, config);

  // response interceptor here
  return response;
};

const elements = (
  <API
    tryItCredentialsPolicy={"same-origin"}
    apiDescriptionUrl={window.wpOpenApi.endpoint}
    router={"hash"}
    layout={"sidebar"}
    hideTryIt={window.wpOpenApi.options.hideTryIt}
  />
);

render(elements, elementsAppContainer);
