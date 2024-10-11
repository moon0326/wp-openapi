/**
 * External dependencies
 */
import { render } from "@wordpress/element";
import { API } from "@stoplight/elements";
import "@stoplight/elements/styles.min.css";

const elementsAppContainer = document.getElementById("elements-app");
const { fetch: originalFetch } = window;

function normalizeHeaders(headers) {
  if (headers instanceof Headers) {
    return headers;
  }
  
  // Convert plain object or array of key-value pairs to Headers
  return new Headers(headers);
}

window.fetch = (resource, config) => {
  if ( ! config ) {
    config = {};
  }

  if ( ! config.headers ) {
    config.headers = new Headers();
  } else {
	config.headers = normalizeHeaders(config.headers);
  }

  if (
    resource.indexOf("wp-json") !== false &&
    config?.headers &&
    config.headers.get('X-WP-Nonce') === null
  ) {
	config.headers.set("X-WP-Nonce", window.wpOpenApi.nonce);
  }


  return originalFetch( resource, config );
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
