<?php

namespace Jarviz\Controller;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Jarviz\Auth\Token;
use Jarviz\DB\Clients;
use Jarviz\DB\DBSets;
use Jarviz\Exception\AuthLevelException;
use Jarviz\Exception\JSONableException;
use Jarviz\Shared\Generators\GUID;
use Jarviz\Shared\Server\Request;
use Mailgun\Mailgun;
use Respect\Validation\Validator;

/**
 * Class ClientController
 * Данный класс содержит в себе методы для управления клиентами и получения информации о них
 * @package Jarviz\Controller
 */
class ClientController extends ApiController
{
  private static $defaultParams = [
    'active'      => 1,
    'restore_key' => 'none',
    'password'    => 'none',
    'salt'        => 'none',
    'referer'     => 0,
    'name'        => 'none',
    'email'       => 'none',
    'webpage'     => 'none',
    'phone'       => 'none',
    'src_ref'     => 'none',
    'utm_source'  => 'none',
    'utm_name'    => 'none',
    'utm_medium'  => 'none',
    'utm_term'    => 'none',
    'utm_content' => 'none',
    'geo_country' => 'none',
    'geo_city'    => 'none',
    'ip'          => 'none',
    'created_at'  => '',
    'utm_hash'    => 'none'
  ];

  /**
   * Проверка и подставление дефолтных значений в массив
   * @param array $params
   * @return mixed
   * @throws JSONableException
   */
  private function checkDefaultParams($params)
  {
    foreach (self::$defaultParams as $key => $value) {
      $params[$key] = (!isset($params[$key])) ? $value : $params[$key];
      if($key = 'created_at') {
        if(!isset($params[$key])) {
          $obj = new \DateTime('2000-01-01');
          $params[$key] = $obj->format('Y-m-d H:i:s');
        }
      }
    }
    /** Проверяем, если в параметры передано больше значений чем нужно, показываем ошибку */
    if (count($params) > count(self::$defaultParams)) {
      throw new JSONableException('Wrong params');
    }

    return $params;
  }

  /**
   * Возвращает все данные о текущем клиенте
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER
   */
  public function getAll()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE referer = :referer");
    $sth->bindValue(':referer', $this->_application->getCurrentClient());
    $sth->execute();
    /** @var Array $clients */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  /**
   * Возвращает все имена похожие на значение параметра $arg
   * @param string $arg
   */
  public function getNames($arg)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT DISTINCT name FROM jarviz_io.dbo.clients WHERE name LIKE '%$arg%'");
    $sth->execute();
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Names'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
    return $this->_result;
  }

  /**
   * Возвращает все данные о клиентах
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_ADMIN
   */
  public function getAllAdmin()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients");
    $sth->execute();
    /** @var Array $clients */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  /**
   * Возвращает все данные о клиенте по реферерру
   * @param int $operator
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER_PARTNER
   */
  public function getByReferer($operator)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER_PARTNER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE referer = :referer");
    $sth->bindValue(':referer', $operator);
    $sth->execute();
    /** @var Array $clients */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  /**
   * Возвращает все данные о клиенте по заданному виджету
   * @param int $widget
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER
   */
  public function getByWidget($widget)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients INNER JOIN jarviz_io.dbo.widget ON widget.client_id = clients.id WHERE client_id = :client_id");
    $sth->bindValue(':client_id', $this->_application->getCurrentClient());
    $sth->execute();
    /** @var Array $widgets */
    $clients = array();
    while ($client = $sth->fetchObject('Jarivz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  /**
   * Возвращает данные о клиенте по ID
   *
   * @param int $id
   * @return array
   * @throws AuthLevelException
   */
  public function getByID($id)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE id = :id");
    $sth->bindValue(':id', $id);
    $sth->execute();
    /** @var Array $widgets */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;

    return $this->_result;
  }

  /**
   * Возвращает данные о клиенте по ID (включая Оплату и Средние затраты)
   *
   * @param int $id
   * @return array
   * @throws AuthLevelException
   */
    public function getClientInfoByID($id)
    {
        $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
        /** @var \PDO $dbInstance */
        $dbInstance = DBSets::getInstance()->getConnection();
        $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE id = :id");
        $sth->bindValue(':id', $id);
        $sth->execute();

        /*
        $clients = array();
        while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
            $clients[] = $client;
        $this->_result = $clients;
        */

        $clientByIdResult = $sth->fetch(\PDO::FETCH_ASSOC);

        // Send another request for billing table
        $sth = $dbInstance->prepare("SELECT COUNT(*) AS numberOfPayments FROM jarviz_io.dbo.billing
                                      WHERE client_id = :client_id
                                      AND payed_at >= '01/01/1970'
                                      AND amount > 0");

        $sth->bindValue(':client_id', $id);
        $sth->execute();
        $result = $sth->fetch();
        // Now $result["NumberOfPayments"] holds NumberOfPayments

        $clientByIdResult["numberOfPayments"] = $result["numberOfPayments"];

        // Request for total sum of |amount| where amount<0
        $sth = $dbInstance->prepare("SELECT SUM(ABS(amount)) AS averageExpenses FROM jarviz_io.dbo.billing
                                      WHERE client_id = :client_id
                                      AND payed_at >= '01/01/1970'
                                      AND amount < 0");

        $sth->bindValue(':client_id', $id);
        $sth->execute();
        $result = $sth->fetch();

        $period = time() - strtotime($clientByIdResult["created_at"]);
        $daysSinceCreated = ceil( $period/(60*60*24) );

        $averageExpenses = (float) $result["averageExpenses"];
        if ( $daysSinceCreated > 0 )
            $averageExpenses /= $daysSinceCreated;

        $clientByIdResult["averageExpenses"] = round($averageExpenses * 30, 4);

        // add new array to conform with javascript code
        $returnArray = array();
        $returnArray[] = $clientByIdResult;

        return $returnArray;

    }


  /**
   * Создает нового клиента
   * @param int $active
   * @param string $restore_key
   * @param string $password
   * @param string $salt
   * @param int $referer
   * @param string $name
   * @param string $email
   * @param string $webpage
   * @param string $phone
   * @param string $src_ref
   * @param string $utm_source
   * @param string $utm_name
   * @param string $utm_medium
   * @param string $utm_term
   * @param string $utm_content
   * @param string $geo_country
   * @param string $geo_city
   * @param string $ip
   * @param datetime $created_at
   * @param string $utm_hash
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_USER
   */
  public function newClient($active,$restore_key,$password,$salt,$referer,$name,$email,$webpage,$phone,$src_ref,
                            $utm_source,$utm_name,$utm_medium,$utm_term,$utm_content,$geo_country,$geo_city,$ip,
                            $created_at,$utm_hash)
  {
    //$this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var array $params */
    $params = [
      'active'      => $active,
      'restore_key' => $restore_key,
      'password'    => $password,
      'salt'        => $salt,
      'referer'     => $referer,
      'name'        => $name,
      'email'       => $email,
      'webpage'     => $webpage,
      'phone'       => $phone,
      'src_ref'     => $src_ref,
      'utm_source'  => $utm_source,
      'utm_name'    => $utm_name,
      'utm_medium'  => $utm_medium,
      'utm_term'    => $utm_term,
      'utm_content' => $utm_content,
      'geo_country' => $geo_country,
      'geo_city'    => $geo_city,
      'ip'          => $ip,
      'created_at'  => $created_at,
      'utm_hash'    => $utm_hash
    ];

    if (!empty($params) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();

      $sth = $dbInstance->prepare('INSERT INTO jarviz_io.dbo.clients (active, restore_key, password, salt,
                                    referer, name, email, webpage, phone, src_ref, utm_source, utm_name, utm_medium,
                                    utm_term, utm_content, geo_country, geo_city, ip, created_at, utm_hash) VALUES
                                    (:active,:restore_key,:password,:salt,:referer,:name,:email,:webpage,
                                    :phone,:src_ref,:utm_source,:utm_name,:utm_medium,:utm_term,:utm_content,
                                    :geo_country, :geo_city, :ip, :created_at, :utm_hash)');
      /** @var Array $params */
      $params = $this->checkDefaultParams($params);
      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }

      $sth->execute($params);
      return $dbInstance->lastInsertId();
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Обновляет клиента
   * @param int $id
   * @param int $active
   * @param string $restore_key
   * @param string $password
   * @param string $salt
   * @param int $referer
   * @param string $name
   * @param string $email
   * @param string $webpage
   * @param string $phone
   * @param string $src_ref
   * @param string $utm_source
   * @param string $utm_name
   * @param string $utm_medium
   * @param string $utm_term
   * @param string $utm_content
   * @param string $geo_country
   * @param string $geo_city
   * @param string $ip
   * @param datetime $created_at
   * @param string $utm_hash
   * @throws AuthLevelException
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_ADMIN
   */
  public function updateClient($id, $active,$restore_key,$password,$salt,$referer,$name,$email,$webpage,$phone,$src_ref,
                               $utm_source,$utm_name,$utm_medium,$utm_term,$utm_content,$geo_country,$geo_city,$ip,
                               $created_at,$utm_hash)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var array $params */
    $params = [
      'active'      => $active,
      'restore_key' => $restore_key,
      'password'    => $password,
      'salt'        => $salt,
      'referer'     => $referer,
      'name'        => $name,
      'email'       => $email,
      'webpage'     => $webpage,
      'phone'       => $phone,
      'src_ref'     => $src_ref,
      'utm_source'  => $utm_source,
      'utm_name'    => $utm_name,
      'utm_medium'  => $utm_medium,
      'utm_term'    => $utm_term,
      'utm_content' => $utm_content,
      'geo_country' => $geo_country,
      'geo_city'    => $geo_city,
      'ip'          => $ip,
      'created_at'  => $created_at,
      'utm_hash'    => $utm_hash
    ];

    if (!empty($params) && !empty($id) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();

      $sth = $dbInstance->prepare("UPDATE jarviz_io.dbo.clients
                                            SET creted_at = :creted_at,
                                            acitve = :active,
                                            restore_key = :restore_key,
                                            password = :password,
                                            salt = :salt,
                                            referer = :referer,
                                            name = :name,
                                            email = :email,
                                            webpage = :webpage,
                                            phone = :phone,
                                            src_ref = :src_ref,
                                            utm_source = :utm_source,
                                            utm_name = :utm_name,
                                            utm_medium = :utm_medium,
                                            utm_term = :utm_term,
                                            utm_content = :utm_content,
                                            geo_country = :geo_country,
                                            geo_city = :geo_city,
                                            ip = :ip,
                                            created_at = :created_at,
                                            utm_hash = :utm_hash WHERE id = :id");
      /** @var Array $params */
      $params = $this->checkDefaultParams($params);
      $params[] = $id;

      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }
      if (!$sth->execute())
        throw new JSONableException('Error in updating a client');
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Метод для фильтрации и поиска клиентов.
   *
   * @param string $name
   * @param int $type
   * @param string $created_at
   * @param string $arg
   * @return string
   * @throws JSONableException
   */
  public function clientsSelectAdmin($name = null, $type = null, $created_at = null, $arg = null)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);

    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();

            // Slightly modified code
      $params = [
          'name'        => ($name != null) ? "clients.name = :name" : null,
          'type'        => ($type != null) ? "widget.widget_type = :type" : null,
          'created_at'  => ($created_at != null) ? "CONVERT(CHAR(10),clients.created_at,20) = :created_at" : null
      ];

    if ($arg == null) {
      //Переменная для подставки WHERE в запрос
      $where = ($params['name'] != '' || $params['type'] != '' || $params['created_at'] != '')
        ? 'WHERE ' : null;

      $i = 0;
      $string = '';
      //Цикл для подставки кусков запроса в переменную для сборки запроса
      foreach ($params as $key => $value) {
        if ($value != null) {
          if ($i == 0) {
            //Если это первый элемент после WHERE то перед ним запятую не ставим
            $string = $value;
            $i++;
          } else {
            //Если не первый, то ставим запятую
            $string .= ' AND ' . $value;
          }
        }
      }

      // Let's construct SQL query
      // We need to find balance and last date of payment for each client
      /*1) Из таблицы billing берутся только ряды amount>0
      2) Потом в получившейся таблице каждый ряд в группе с одинаковым client_id
      нумеруется начиная с самой поздней даты payed_at
      3) Из получившейся таблицы берут только ряд, являющимся первым в нумерации
      в группе с одинаковым client_id.
      */
      // для таблиц нужно использовать AS (который кажется излишним),
      // потому что иначе SQL будет выдавать ошибку.

        $query = "SELECT clients.id,
                      clients.name,
                      clients.email,
                      clients.phone,
                      clients.webpage,
                      clients.created_at,
                      billingRN.amount,
                      billingRN.payed_at,
                      widget.widget_type,
                      widget.title,
                      billingBalance.balance
                FROM jarviz_io.dbo.clients AS clients
                LEFT JOIN jarviz_io.dbo.widget AS widget
                ON clients.id = widget.client_id
                LEFT JOIN (SELECT id AS billing_id,
                                   client_id,
                                   amount,
                                   payed_at
                      FROM (SELECT id,
                                   client_id,
                                   amount,
                                   payed_at,
                                   ROW_NUMBER() OVER(
                                   PARTITION BY client_id
                                   ORDER BY payed_at DESC) AS rownumb
                            FROM (SELECT id,
                                   client_id,
                                   amount,
                                   payed_at
                                   FROM jarviz_io.dbo.billing
                                   WHERE amount>0) AS plusAmountBilling ) AS T
                            WHERE rownumb = 1 ) AS billingRN
                ON clients.id = billingRN.client_id
                LEFT JOIN (SELECT client_id,
                                  SUM(amount) AS balance
                          FROM jarviz_io.dbo.billing
                          GROUP BY client_id) AS billingBalance
                ON clients.id = billingBalance.client_id " . $where . $string;


      $sth = $dbInstance->prepare($query);

      //Если есть параметры для фильтрации то подставляем их в запрос
      if ($string != '') {
        foreach ($params as $key => $value) {
          if ($value != null) {
            $sth->bindValue($key, $$key);
          }
        }
      }
      $sth->execute();

    } elseif (!empty($arg)) {

        // Additional code
        $i = 0;
        $string = '';
        //Цикл для подставки кусков запроса в переменную для сборки запроса
        foreach ($params as $key => $value) {
            if ($value != null) {
                if ($i == 0) {
                    //Если это первый элемент после WHERE то перед ним запятую не ставим
                    $string = $value;
                    $i++;
                } else {
                    //Если не первый, то ставим запятую
                    $string .= ' AND ' . $value;
                }
            }
        }

      $string = ($string != null) ? $string = ' AND ' . $string : null;

      // Let's construct SQL query
      // We need to find balance and last date of payment for each client
      /*1) Из таблицы billing берутся только ряды amount>0
      2) Потом в получившейся таблице каждый ряд в группе с одинаковым client_id
      нумеруется начиная с самой поздней даты payed_at
      3) Из получившейся таблицы берут только ряд, являющимся первым в нумерации
      в группе с одинаковым client_id.
      */
      // для таблиц нужно использовать AS (который кажется излишним),
      // потому что иначе SQL будет выдавать ошибку.

      $query = "SELECT clients.id,
                    clients.name,
                    clients.email,
                    clients.phone,
                    clients.webpage,
                    clients.created_at,
                    billingRN.amount,
                    billingRN.payed_at,
                    widget.widget_type,
                    widget.title,
                    billingBalance.balance
              FROM jarviz_io.dbo.clients AS clients
              LEFT JOIN jarviz_io.dbo.widget AS widget
              ON clients.id = widget.client_id
              LEFT JOIN (SELECT id AS billing_id,
                                 client_id,
                                 amount,
                                 payed_at
                    FROM (SELECT id,
                                 client_id,
                                 amount,
                                 payed_at,
                                 ROW_NUMBER() OVER(
                                 PARTITION BY client_id
                                 ORDER BY payed_at DESC) AS rownumb
                          FROM (SELECT id,
                                 client_id,
                                 amount,
                                 payed_at
                                 FROM jarviz_io.dbo.billing
                                 WHERE amount>0) AS plusAmountBilling ) AS T
                          WHERE rownumb = 1 ) AS billingRN
              ON clients.id = billingRN.client_id
              LEFT JOIN (SELECT client_id,
                                SUM(amount) AS balance
                        FROM jarviz_io.dbo.billing
                        GROUP BY client_id) AS billingBalance
              ON clients.id = billingBalance.client_id
              WHERE (clients.restore_key LIKE '%$arg%'
                  OR clients.name LIKE '%$arg%'
                  OR clients.email LIKE '%$arg%'
                  OR clients.webpage LIKE '%$arg%'
                  OR clients.phone LIKE '%$arg%'
                  OR clients.src_ref LIKE '%$arg%'
                  OR clients.utm_source LIKE '%$arg%'
                  OR clients.utm_name LIKE '%$arg%'
                  OR clients.utm_medium LIKE '%$arg%'
                  OR clients.utm_term LIKE '%$arg%'
                  OR clients.utm_content LIKE '%$arg%'
                  OR clients.geo_country LIKE '%$arg%'
                  OR clients.geo_city LIKE '%$arg%'
                  OR clients.ip LIKE '%$arg%'
                  OR clients.utm_hash LIKE '%$arg%')" . $string;


      $sth = $dbInstance->prepare($query);
      if ($string != '') {
        foreach ($params as $key => $value) {
          if ($value != null) {
            $sth->bindParam($key, $$key);
          }
        }
      }
      $sth->execute();
    } else {
      throw new JSONableException('Error when calling method');
    }

    $clients = array();

    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;


    return $clients;
  }


  public function getByEmail($email)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ANON);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE email = :email");
    $sth->bindValue(':email', $email);
    $sth->execute();
    /** @var Array $clients */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  public function getByRestoreKey($key)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ANON);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE restore_key = :key");
    $sth->bindValue(':key', $key);
    $sth->execute();
    /** @var Array $clients */
    $clients = array();
    while ($client = $sth->fetchObject('Jarviz\DB\Clients'))
      $clients[] = $client;
    /** @var Array _result */
    $this->_result = $clients;
  }

  /**
   * Функция осуществления регистрации
   * @param string $email
   * @param string $pass
   * @param string $webpage
   * @param string $phone
   * @param string $name
   * @param string $referer
   * @param string $utm_source
   * @param string $utm_name
   * @param string $utm_medium
   * @param string $utm_term
   * @param string $utm_content
   * @internal param $marketing
   */
  public function register($email, $pass, $webpage, $phone, $name, $referer="none",$utm_source="none",$utm_name="none",
                           $utm_medium="none",$utm_term="none",$utm_content="none")
  {
    if(!Validator::email()->validate($email))
      throw new JSONableException("{$email} is not valid email address",1);
    if(!Validator::phone()->validate($phone))
      throw new JSONableException("{$phone} is not valid phone number",2);
    if(!Validator::length(1)->validate($name))
      throw new JSONableException("We need know your name",3);
    if(!Validator::length(1)->validate($pass))
      throw new JSONableException("You need set password.",4);
    $this->getByEmail($email);
    if(!empty($this->_result))
      throw new JSONableException("User with email {$email} already registered",1);

    $salt= GUID::get();
    $ip = Request::getClientIp();
    $hashed_pass = md5($pass.$salt);
    try{
      $reader = new Reader($_SERVER['DOCUMENT_ROOT'].'/data/GeoLite2-City.mmdb');
      $record = $reader->city($ip);
      $country = $record->country->name;
      $city = $record->city->name;
    }catch (AddressNotFoundException $e){
      $country = "UNKNOWN";
      $city =  "UNKNOWN";
    }
    $utm_hash = md5($utm_source.':'.$utm_name.':'.$utm_medium.':'.$utm_term.':'.$utm_content);
    $id = $this->newClient(true,GUID::get(),$hashed_pass,$salt,0,$name,$email,$webpage,$phone,$referer,$utm_source,
      $utm_name,$utm_medium,$utm_term,$utm_content,$country,$city,$ip,date('Y-m-d H:i:s'),
      $utm_hash);

    $token_string = Token::generateNewTemp($id,"+2 week");
    $loader = new \Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"].'/mail');
    $twig = new \Twig_Environment($loader, []);
    $mailBody = $twig->render('welcome.twig', array('name' => $name));
    $mg = new Mailgun("key-199cfcb1ae82da63c960477d1957d138");
    $domain = "jarviz.io";
    $mg->sendMessage($domain,[
      'from'    => 'hal9000@jarviz.io',
      'to'      => $email,
      'subject' => 'Welcome to Jarviz.io',
      'html'    => $mailBody]);
    return ['result'=>["email"=>$email,"token"=>$token_string]];
  }

  /**
   * Функция восстановления пароля
   * @param $email
   */
  public function forgotPassword($email)
  {
    $this->getByEmail($email);
    if(empty($this->_result))
      return [];
    /** @var Clients $user */
    $user = $this->_result[0];
    $newkey = GUID::get();
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("UPDATE jarviz_io.dbo.clients SET restore_key = :restore_key WHERE id = :id");
    $sth->bindValue(':restore_key',$newkey);
    $sth->bindValue(':id',$user->id);
    $sth->execute();
    $loader = new \Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"].'/mail');
    $twig = new \Twig_Environment($loader, []);
    $mailBody = $twig->render('restore.twig', array('name' => $user->name,'key'=>$newkey));
    $mg = new Mailgun("key-199cfcb1ae82da63c960477d1957d138");
    $domain = "jarviz.io";
    $mg->sendMessage($domain,[
      'from'    => 'hal9000@jarviz.io',
      'to'      => $email,
      'subject' => 'Restore password on Jarviz.io',
      'html'    => $mailBody]);
    return [];
  }

  /**
   * Функция подтверждения восстановления пароля
   * @param $code
   * @param $new_pass
   */
  public function restorePassword($key, $password)
  {
    $this->getByRestoreKey($key);
    if(empty($this->_result))
      throw new JSONableException('Wrong restore key',1);
    /** @var Clients $user */
    $user = $this->_result[0];
    $newkey = GUID::get();
    $newpass = md5($password.$user->salt);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("UPDATE jarviz_io.dbo.clients SET restore_key = :restore_key, password = :password WHERE id = :id");
    $sth->bindValue(':restore_key',$newkey);
    $sth->bindValue(':password',$newpass);
    $sth->bindValue(':id',$user->id);
    $sth->execute();
    $token_string = Token::generateNewTemp($user->id,"+2 week");
    return ['result'=>["email"=>$user->email,"token"=>$token_string]];
  }

  /**
   * Функция входа в систему
   * @param $email
   * @param $pass
   * @throws JSONableException
   */
  public function loginInteractive($email, $password)
  {
    if (!empty($email) && !empty($password)) {
      $this->getByEmail($email);
      if(empty($this->_result))
        throw new JSONableException('Wrong email or password',1);
      /** @var Clients $user */
      $user = $this->_result[0];
      if(strtolower(md5($password.$user->salt))!=strtolower($user->password))
        throw new JSONableException('Wrong email or password',1);
      //$token_string = Token::generateNewTemp($user->id,"+2 week");
        $token_string = Token::generateInitToken($user->id,"+2 week",$user->init_token_level);
      return ['result'=>["email"=>$email,"token"=>$token_string]];
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }


  /**
   *  Для удобства обработки ajax-запроса при входе в систему
   * удобно совместить с методом проверки валидности переданного токена
   * @param string $token
   * @param string $email
   * @param string $password
   * @return array
   * @throws JSONableException
   */
  public function validateTokenAndLoginAdmin($token, $email, $password) {
        if( AccessTokenController::checkTokenForAdmin($token) ) {
            return ['validToken'=>true];
        } else {
            return $this->loginInteractive($email, $password);
        }
    }

  public function getCurrentClient(){
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.clients WHERE id = :id");
    $sth->bindValue(':id', $this->_application->getCurrentClient());
    $sth->execute();
    /** @var Clients $client */
    $client = $sth->fetchObject('Jarviz\DB\Clients');
    return ['result'=>['id'=>$client->id,
      'active'=>$client->active,
      'name'=>$client->name,
      'email'=>$client->email,
      'webpage'=>$client->webpage,
      'phone'=>$client->phone]];
  }
} 