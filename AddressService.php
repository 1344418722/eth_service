<?php

namespace App\Service;

use App\Service\Ethereum\Client;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use Web3p\EthereumUtil\Util;

class AddressService
{
    //生成随机助记词
    public static function mnemonic()
    {

        $random = new Random();
        $entropy = $random->bytes(Bip39Mnemonic::MIN_ENTROPY_BYTE_LEN);
        $bip39 = MnemonicFactory::bip39();
        $mnemonic = $bip39->entropyToMnemonic($entropy);
        return $mnemonic;// 助记词
    }

    //助记词获取私钥
    public static function getPrivateKey($mnemonic)
    {
        $seedGenerator = new Bip39SeedGenerator();
        $seed = $seedGenerator->getSeed($mnemonic);
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);
        $hardened = $master->derivePath("44'/60'/0'/0/0");
        return $hardened->getPrivateKey()->getHex();
    }

    //助记词获取地址
    public static function publicKeyToAddress($mnemonic)
    {
        $seedGenerator = new Bip39SeedGenerator();
    // 通过助记词生成种子，传入可选加密串'hello'
        $seed = $seedGenerator->getSeed($mnemonic);
        $hdFactory = new HierarchicalKeyFactory();
        $master = $hdFactory->fromEntropy($seed);
        $util = new Util();
        $hardened = $master->derivePath("44'/60'/0'/0/0");
        return $util->publicKeyToAddress($util->privateKeyToPublicKey($hardened->getPrivateKey()->getHex()));
    }

    /**
     * 获取地址私钥
     * @return array
     */
    public static function getAddressPrivate()
    {
        $client = new Client();
        return $client->newAccount();
    }

}
