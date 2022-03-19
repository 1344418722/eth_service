<?php

namespace App\Service;

use kornrunner\Keccak;
use App\Service\Ethereum\Client as EthereumClient;
use App\Service\Ethereum\Utils;

class TradService
{
    use \App\Service\Utils;

    protected static $Host = 'https://data-seed-prebsc-1-s1.binance.org:8545/';

    public function __construct($host = '')
    {
        self::$Host = config('chain.node_url');
        if ($host != '') {
            self::$Host = $host;
        }
    }

    /**
     * 获取地址余额
     * @param $address
     * @return string
     */
    public static function getBalance($address)
    {
        $client = new EthereumClient(self::$Host);
        $balance = $client->eth_getBalance($address, 'latest');
        $balance = gmp_strval($balance, 10);
        return $balance;
    }

    /**
     * 获取授权数量
     * @param $address
     * @param $power_address
     * @param $contract
     * @return
     */
    public static function getAllowance($address, $power_address, $contract)
    {
        $hash = Keccak::hash("allowance(address,address)", 256);
        $data_1 = mb_substr($hash, 0, 8, 'utf-8');
        $address = self::remove0x($address);
        $data_2 = str_pad(0, 64 - strlen($address), "0", STR_PAD_LEFT) . $address;
        $power_address = self::remove0x($power_address);
        $data_3 = str_pad(0, 64 - strlen($power_address), "0", STR_PAD_LEFT) . $power_address;
        $data = '0x' . $data_1 . $data_2 . $data_3;
        $number = self::eth_call($data, $contract);
        return gmp_strval($number, 10);
    }

    /**
     * 获取地址合约数量
     * @param $address
     * @param $power_address
     * @param $contract
     * @return
     */
    public static function getBalaceContract($address, $contract)
    {
        $hash = Keccak::hash("balanceOf(address)", 256);
        $data_1 = mb_substr($hash, 0, 8, 'utf-8');
        $address = self::remove0x($address);
        $data_2 = str_pad(0, 64 - strlen($address), "0", STR_PAD_LEFT) . $address;
        $data = '0x' . $data_1 . $data_2;
        $number = self::eth_call($data, $contract);
        return gmp_strval($number, 10);
    }


    /**
     * 发送主币
     * @param $from_address
     * @param $private_key
     * @param $to_address
     * @param $value
     * @return mixed
     * @throws \Exception
     */
    public static function sendMainCoin($from_address, $private_key, $to_address, $value)
    {
        $url = self::$Host;
        $address = $to_address;
        $number = $value;
//        $contract = $data['contract'];
        $privateKey = $private_key;
        $from = $from_address;
        $client = new EthereumClient($url);
        $client->addPrivateKeys([$privateKey]);
        $trans = [
            "from"  => $from,
            "to"    => $address,
            "value" => Utils::ethToWei($number, true),
            "data"  => '0x',
        ];
        $trans['gas'] = dechex(hexdec($client->eth_estimateGas($trans)) * 1.5);
        $trans['gasPrice'] = $client->eth_gasPrice();
        $trans['nonce'] = $client->eth_getTransactionCount($from, 'pending');
        $txid = $client->sendTransaction($trans);
        return $txid;
    }

    /**
     *发送合约交易
     * @param $from_address //发送地址
     * @param $private_key //发送地址私钥
     * @param $to_address //接收地址
     * @param $contract //合约地址
     * @param $value //数量
     * @param $decimals_pow //精度已平方过的
     * @return mixed
     * @throws \Exception
     */
    public static function sendContractCoin($from_address, $private_key, $to_address, $contract, $value, $decimals_pow)
    {
        $url = self::$Host;
        $address = $to_address;
        $number = $value;
        $privateKey = $private_key;
        $from = $from_address;
        //处理精度
        $number = bcmul($decimals_pow, $number);
//        $number = $decimals_pow * $number;
        $client = new EthereumClient($url);
        $client->addPrivateKeys([$privateKey]);
        $trans = [
            "from"  => $from,
            "to"    => $contract,//合约地址
            "value" => '0x0'
            //    "data" => '0x',
        ];
        $hash = Keccak::hash("transfer(address,uint256)", 256);
        $data_1 = mb_substr($hash, 0, 8, 'utf-8');
        $remove0xAddress = self::remove0x($address);
        //        0填充
        $data_2 = str_pad(0, 64 - strlen($remove0xAddress), "0", STR_PAD_LEFT) . $remove0xAddress;
        $number = self::remove0x(Utils::decToHex($number));
        $data_3 = str_pad(0, 64 - strlen($number), "0", STR_PAD_LEFT) . $number;
        $trans['data'] = '0x' . $data_1 . $data_2 . $data_3;
//        $trans['gas'] = dechex(hexdec($client->eth_estimateGas($trans)) * 1.5);
        $trans['gas'] = '210000';
        $trans['gasPrice'] = $client->eth_gasPrice();
        $nonce = self::getNonce($from);
        $nonce = gmp_strval($nonce, 16);
        $trans['nonce'] = $nonce;
        return $client->sendTransaction($trans);
    }

    /**
     * 子地址发起授权
     * @param $from
     * @return mixed
     */
    public static function sendApprove($from_address, $private_key, $to_address, $contract)
    {
        $url = self::$Host;
        $address = $to_address;
        $privateKey = $private_key;
        $from = $from_address;
        //处理精度
        $client = new EthereumClient($url);
        $client->addPrivateKeys([$privateKey]);
        $trans = [
            "from"  => $from,
            "to"    => $contract,//合约地址
            "value" => '0x0'
            //    "data" => '0x',
        ];
        $hash = Keccak::hash("approve(address,uint256)", 256);
        $data_1 = mb_substr($hash, 0, 8, 'utf-8');
        $remove0xAddress = self::remove0x($address);
        $data_2 = str_pad(0, 64 - strlen($remove0xAddress), "0", STR_PAD_LEFT) . $remove0xAddress;
        $number = '0000000000000000000fffffffffffffffffffff';
        $data_3 = str_pad(0, 64 - strlen($number), "0", STR_PAD_LEFT) . $number;
        $trans['data'] = '0x' . $data_1 . $data_2 . $data_3;
        $trans['gas'] = dechex(hexdec($client->eth_estimateGas($trans)) * 1.5);
//        $trans['gas'] = '210000';
        $trans['gasPrice'] = $client->eth_gasPrice();
        $nonce = self::getNonce($from);
        $nonce = gmp_strval($nonce, 16);
        $trans['nonce'] = $nonce;
        return $client->sendTransaction($trans);
    }

    /**
     * 地址加白名单
     * @param $from
     * @return mixed
     */
    public static function sendWhite($from_address, $private_key, $to_address, $contract)
    {
        $url = self::$Host;
        $address = $to_address;
        $privateKey = $private_key;
        $from = $from_address;
        //处理精度
        $client = new EthereumClient($url);
        $client->addPrivateKeys([$privateKey]);
        $trans = [
            "from"  => $from,
            "to"    => $contract,//合约地址
            "value" => '0x0'
            //    "data" => '0x',
        ];
        $hash = Keccak::hash("setWhiteAddress(address,bool)", 256);
        $data_1 = mb_substr($hash, 0, 8, 'utf-8');
        $remove0xAddress = self::remove0x($address);
        $data_2 = str_pad(0, 64 - strlen($remove0xAddress), "0", STR_PAD_LEFT) . $remove0xAddress;
        $bool = '1';
        $data_3 = str_pad(0, 64 - strlen($bool), "0", STR_PAD_LEFT) . $bool;
        $trans['data'] = '0x' . $data_1 . $data_2 . $data_3;
        $trans['gas'] = dechex(hexdec($client->eth_estimateGas($trans)) * 1.5);
//        $trans['gas'] = '210000';
        $trans['gasPrice'] = $client->eth_gasPrice();
        $nonce = self::getNonce($from);
        $nonce = gmp_strval($nonce, 16);
        $trans['nonce'] = $nonce;
        return $client->sendTransaction($trans);
    }

    /**
     * 授权转账
     * @param $from
     * @return mixed
     */
    public static function sendTransferFrom($address, $power_address, $power_private_key, $amount, $payee_address, $contract)
    {
        $url = self::$Host;

        //处理精度
        $client = new EthereumClient($url);
        $client->addPrivateKeys([$power_private_key]);
        $trans = [
            "from"  => $power_address,
            "to"    => $contract,//合约地址
            "value" => '0x0'
            //    "data" => '0x',
        ];
        $hash = Keccak::hash("transferFrom(address,address,uint256)", 256);
        $method = mb_substr($hash, 0, 8, 'utf-8');
        $address = self::remove0x($address);
        $address = str_pad(0, 64 - strlen($address), "0", STR_PAD_LEFT) . $address;

        $payee_address = self::remove0x($payee_address);
        $payee_address = str_pad(0, 64 - strlen($payee_address), "0", STR_PAD_LEFT) . $payee_address;


        $amount = self::remove0x($amount);
        $amount = str_pad(0, 64 - strlen($amount), "0", STR_PAD_LEFT) . $amount;

        $trans['data'] = '0x' . $method . $address . $payee_address . $amount;
//        $trans['gas'] = dechex(hexdec($client->eth_estimateGas($trans)) * 1.5);
        $trans['gas'] = '210000';
        $trans['gasPrice'] = $client->eth_gasPrice();
        $nonce = self::getNonce($power_address);
        $nonce = gmp_strval($nonce, 16);
        $trans['nonce'] = $nonce;
        return $client->sendTransaction($trans);
    }


    //获取交易数量
    public static function getNonce($from)
    {
        $client = new EthereumClient(self::$Host);
        $nonce = $client->eth_getTransactionCount($from, 'latest');
        return $nonce;
    }

    public static function eth_call($data, $to, $from = '0x0000000000000000000000000000000000000000')
    {
        $data = json_encode(
            [
                'jsonrpc' => '2.0',
                'method'  => 'eth_call',
                'id'      => '1',
                'params'  => [[
                    'data' => $data,
                    'from' => $from,
                    'to'   => $to
                ], 'latest'],
            ]);
        $result = HttpService::send_post(self::$Host, $data);
        $result = json_decode($result, true);
        if (isset($result['result'])) {
            return $result['result'];
        } else {
            return false;
        }
    }

}
