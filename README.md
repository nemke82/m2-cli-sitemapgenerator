# m2-cli-sitemapgenerator
Magento 2 Extension for generating Sitemap.xml file using Magento CLI command. (php bin/magento)

Compatible with Magento 2.x (including latest 2.3.0)

How to install?

- git clone https://github.com/nemke82/m2-cli-sitemapgenerator.git

OR

Download ZIP archive and extract (example):
wget -c https://github.com/nemke82/m2-cli-sitemapgenerator/archive/master.zip

Once data uploaded to the app/code/ directory run:
- php bin/magento module:enable Neman_Generatesitemapxml
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy

How to run script:
php bin/magento sitemap:generate

By executing php bin/magento command you will see it listed under sitemap section. If you don't have anything configured within Dashboard --> Marketing --> Sitemap, script is going to use/add first store view automatically.

Special note: If you don't wish to install my extension as addon to the php bin/magento tool, you can download sitemapgenerator.php file and execute it from the Magento 2 document root.

Example:
php sitemapgenerator.php sitemap:generate
