#!/bin/sh

echo "(CIDeploy)"

# execute MTA build
echo "Build MTA..."
java -jar ./tools/mta.jar --mtar tmp/app.mtar --build-target=NEO build

# deploy to SAP Cloud Platform
echo "Deploy MTA..."
./tools/neo-java-web-sdk/tools/neo.sh deploy-mta deploy-mta.properties --password "mySCPpassword"

