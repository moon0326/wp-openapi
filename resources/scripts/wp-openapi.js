/**
 * External dependencies
 */
import { render } from "@wordpress/element";
import { API } from "@stoplight/elements";
import "@stoplight/elements/styles.min.css";

const elementsAppContainer = document.getElementById("elements-app");
const { fetch: originalFetch } = window;

window.fetch = (resource, config) => {
  if ( ! config ) {
    config = {};
  }

  if ( ! config.headers ) {
    config.headers = new Headers();
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
