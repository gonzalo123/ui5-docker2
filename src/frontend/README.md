* ui5 application
* grunt builds dist folder
* ci/deploy-mta.sh deploys the application to SCP

## Start server

> localneo 

start webserver at 8080. Assumes that api server is at 8888

## Requires:
* npm install
* download from https://tools.hana.ondemand.com/#cloud
 * MTA Archive builder to ./ci/tools/mta.jar
 * SAP Cloud Platform Neo Environment SDK to ./ci/tools/neo-java-web-sdk/
 
## Tests
* http://localhost:8080/test/integration/opa5.html