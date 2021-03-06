# jxArtUp - OXID Admin for Scheduled Updates of Products

*Module for backend / admin of OXID eShops for scheduled updates of product attributes*

## Installation and Setup
1. Copy all folders and files under **copy\_this** to the root folder of your shop.
2. Open your browser and login into the OXID admin.
3. Goto **Settings** - **Modules**, select the module _jxArtUp_ and and activate it.
4. Create a CRON job which runs the file /modules/jxmods/jxartup/core/jxartup_cron.php periodically (eg. each 5 minutes).

## Screenshot

**Display of jobs in Admin**

![Job list](https://github.com/job963/jxArtUp/raw/master/docs/img/jxartup_main.png)

![Calendar](https://github.com/job963/jxArtUp/raw/master/docs/img/jxartup_calendar.png)

**Editing a job**

![Editing a job](https://github.com/job963/jxArtUp/raw/master/docs/img/jxartup_edit.png)

## Changelog

* **0.1 Initial Release**
  * Create, edit and remove update jobs
  * Update job for CRON

* **0.2 Calendar Display**
  * Search for product number added
  * calendar display added
  * wrong texts for column headers fixed

*  **0.2.1 Bugfix**
  * Settings for default display added
  * Autofocus of fields fixed

*  **0.2.2 Bugfix**  
  * Problems on Firefox fixed
  * Support for empty fields added   

*  **0.2.3 Bugfix**  
  * Problems on cron job fixed
  * Missing field in create job added
   