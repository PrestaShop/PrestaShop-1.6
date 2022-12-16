### This repository has been archived, it’s no longer maintained but you still can fork it if you need it.
If you look for a new version of the PrestaShop you can go to [this repository](https://github.com/PrestaShop/PrestaShop).

About PrestaShop
--------

PrestaShop is a free and open-source e-commerce web application, committed to providing the best shopping cart experience for both merchants and customers. It is written in PHP, is highly customizable, supports all the major payment services, is translated in many languages and localized for many countries, has a fully responsive design (both front and back office), etc.

To download the latest stable public version of PrestaShop, please go to [the download page][3] on the official PrestaShop site.


About this repository
--------

This repository contains the source code of PrestaShop 1.6, **now legacy**.

Clicking the "Download ZIP" button from the root of this repository will download a development version of PrestaShop 1.6, not a production ready version.

Note that the ZIP file does not contain the default modules: you need to make a recursive clone using your local Git client in order to download their files too. See [CONTRIBUTING.md][2] for more information about using Git and GitHub.

Also, the ZIP file downloaded from GitHub contains resources for developers and designers that are not in the software installer archive, for instance the SCSS sources files for the default themes (in the /default-bootstrap/sass folder) or the unit testing files (in the /tests folder).

Again, if you want the latest stable version of PrestaShop, go to [the download page][3].

Scope
---------

PrestaShop 1.6.1.x is now in "end of life" state. It means that no further develoment will be done on this old version.

However, there is still shops in this version, and a few interested volunteers from the community agreed to help to create a release if, and only if, a serious problem is identified. This include:

- Security issues
- Supporting PHP 7.2

When the volunteers will consider that a patch version is needed, a release will be built and be made available on GitHub only. Those eventual releases will not be available on the PrestaShop company website. This is a pure community initiative.

All issues and pull requests that are not in this list will be closed.

For more details, please read the [1.6.1.X: What’s Next][4] article.

[2]: CONTRIBUTING.md
[3]: http://www.prestashop.com/en/download
[4]: http://build.prestashop.com/news/1.6.1.x-what-s-next/
