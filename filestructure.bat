@echo off
SET PROJECT=almatech_site

echo Creating project: %PROJECT%
mkdir %PROJECT%
cd %PROJECT%

:: Root public files
type nul > index.php
type nul > about.php
type nul > services.php
type nul > service.php
type nul > projects.php
type nul > project.php
type nul > blog.php
type nul > post.php
type nul > contact.php
type nul > config.php
type nul > README.md
type nul > .htaccess

:: Admin root
mkdir admin
cd admin
type nul > index.php
type nul > login.php
type nul > logout.php
type nul > dashboard.php

:: Admin modules
for %%M in (services projects posts leads testimonials users) do (
    mkdir %%M
    cd %%M
    type nul > list.php
    type nul > add.php
    type nul > edit.php
    type nul > delete.php
    cd ..
)

:: Leads extra files
cd leads
type nul > view.php
type nul > update_status.php
cd ..

:: Settings
mkdir settings
cd settings
type nul > index.php
cd ..

:: Media
mkdir media
cd media
type nul > upload.php
type nul > list.php
cd ..

cd ..

:: Includes
mkdir includes
cd includes
type nul > db.php
type nul > auth.php
type nul > csrf.php
type nul > helpers.php
type nul > upload.php
type nul > mail.php
type nul > header.php
type nul > footer.php
type nul > admin_header.php
type nul > admin_sidebar.php
type nul > admin_footer.php
cd ..

:: Assets
mkdir assets
cd assets
mkdir css
mkdir js
mkdir img
type nul > css\main.css
type nul > css\admin.css
type nul > js\main.js
type nul > js\admin.js
cd ..

:: Uploads
mkdir uploads
cd uploads
for %%U in (services projects posts testimonials media) do mkdir %%U
cd ..

echo.
echo =====================================
echo Project structure created successfully
echo Folder: %PROJECT%
echo =====================================
pause
