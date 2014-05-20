php-bitcoin
============
A collection of functions for working with Bitcoin in PHP.

* auto-gen TOC:
{:toc}

##INSTALLATION
 - Create a copy of config_SAMPLE.php as config.php
 - Modify the config.php, filling out as necessary

##Dependencies
* PHP 5.5 or greater

#MPKGEN
 This function lets you generate addresses without requiring a local bitcoind instance. It requires you to have an Electrum MPK (Master Public Key), and can create public keys in a reproducible sequence.

##Dependencies
 - phpecc (in root folder, included in this project)
 - Either GMP or BCMATH php extension (GMP is several times faster than BCMATH)
 - Electrum MPK (Master Public Key)

##Usage
 ```
 require_once(./mpkgen.php);
 echo genBTCAddressFromMPK($master_public_key, $key_offset);`
 ```

##Sources
 - https://github.com/mdanter/phpecc
 - https://github.com/bkkcoins/misc
 - https://github.com/gesman/bitcoin-payments-for-woocommerce
