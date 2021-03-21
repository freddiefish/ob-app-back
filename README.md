# Open Bestuur backend

Is part of the Open Bestuur (ob-app) project. It is an open source project that aims to open up local council decisions for citizens. Back end is PHP based. It pulls data from the city of Antwerp ebesluit portal. 

## Important links

[Old promo video](https://www.youtube.com/watch?v=HktyXjdLedI)

## Related repositories

[Front-end](https://github.com/freddiefish/ob-app)

## Configure 

Edit /resources/config.php

If you choose a Google environment, also edit under resources/environments/environment.php and json files in resources/environments

Place code on any PHP server, create database (Currently set up with Google Firestore).  Create a cron job for regular data-pull intervals.

## Supported Sources

Data extraction support is available for the following source systems:

    + [e-besluit](https://ebesluit.antwerpen.be/)

## Contributing

Please read through our [contributing guidelines](https://github.com/freddiefish/ob-app-back/blob/master/CONTRIBUTING.md). Included are directions for opening issues, coding standards, and notes on development.

## Authors and contributors

 + Frederik Feys ([@FFeys](https://twitter.com/ffeys))

## Copyright and license

The Open Bestuur project is distributed under the [GNU GENERAL PUBLIC LICENSE Version 3](https://opensource.org/licenses/GPL-3.0)

## Future

Future developments can include intelligent text (Machine learning) processing to create relevent citizen intelligence out of the often lengthy council decisions. 

Project demo currently running at [here](https://ob-app-db2b6.firebaseapp.com)