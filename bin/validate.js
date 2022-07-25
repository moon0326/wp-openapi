#!/usr/bin/env node
// wget http://woodev.local/wp-json-openapi?namespace=all
// validate-api wp-json-openapi?namespace=all

import { Validator } from "@seriousme/openapi-schema-validator";
import request from 'request';

function downloadPage(url) {
    return new Promise((resolve, reject) => {
        request(url, (error, response, body) => {
            if (error) reject(error);
            if (response.statusCode != 200) {
                reject('Invalid status code <' + response.statusCode + '>');
            }
            resolve(body);
        });
    });
}

// for functions returning promises
async function myBackEndLogic() {
    try {
        let html = await downloadPage('http://woodev.local/wp-json-openapi?namespace=all');
        html = JSON.parse(html);
		const validator = new Validator();
		const res = await validator.validate(html);
		const specification = validator.specification;
		// specification now contains a Javascript object containing the specification
		if (res.valid) {
		  console.log("Specification matches schema for version", validator.version);
		  const schema = validator.resolveRefs();
		  // schema now contains a Javascript object containing the dereferenced schema
		} else {
		  console.log("Specification does not match Schema");
		  console.log(res.errors);
		}

    } catch (error) {
        console.error('ERROR:');
        console.error(error);
    }
}

myBackEndLogic();

