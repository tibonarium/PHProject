<?php


namespace Jarviz\Controller;


use Jarviz\Auth\Token;
use Jarviz\DB\DBSets;
use Jarviz\Exception\AuthLevelException;
use Jarviz\Exception\JSONableException;

/**
 * Class BillingController
 * Данный класс содержит в себе методы для управления биллингами и получения информации о них
 * @package Jarviz\Controller
 */
class BillingController extends ApiController
{
  private static $defaultParams = [
    'created_at'  => null,
    'description' => 'none',
    'client_id'   => 0,
    'amount'      => 0,
    'payed_at'    => null,
    'status'      => 'none'
  ];

  /**
   * Проверка и подставление дефолтных значений в массив
   *
   * @param array $params
   * @return mixed
   * @throws JSONableException
   */
  private function checkDefaultParams($params)
  {
    foreach (self::$defaultParams as $key => $value) {
      $params[$key] = (!isset($params[$key])) ? $value : $params[$key];
    }
    /** Проверяем, если в параметры передано больше значений чем нужно, показываем ошибку */
    if (count($params) > count(self::$defaultParams)) {
      throw new JSONableException('Wrong params');
    }

    return $params;
  }

  /**
   * Возращает все биллинги из базы данных по текущему пользователю
   *
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER
   */
  public function getAll()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();

    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.billing WHERE client_id = :client_id");
    $sth->bindValue(':client_id', $this->_application->getCurrentClient());
    $sth->execute();
    /** @var Array $billings */
    $billings = array();
    while ($billing = $sth->fetchObject('Jarviz\DB\Billing'))
      $billings[] = $billing;
    /** @var Array _result */
    $this->_result = $billings;
  }

  /**
   * Возвращает все биллинги
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_ADMIN
   */
  public function getAllAdmin()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();

    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.billing");
    $sth->execute();
    /** @var Array $billings */
    $billings = array();
    while ($billing = $sth->fetchObject('Jarviz\DB\Billing'))
      $billings[] = $billing;
    /** @var Array _result */
    $this->_result = $billings;

    return $this->_result;
  }

  /**
   * Возвращает все биллинги по заданному пользователю
   * @param int $client
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER_PARTNER
   */
  public function getByClient($client)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER_PARTNER);
    /** @var PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();

    //$sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.billing WHERE client_id = :client_id");

    // Changed query
    $sth = $dbInstance->prepare("SELECT billing.id AS bil_id,
                                  description,client_id,amount,
                                  billing.created_at AS bil_created_at,
                                  status,payed_at,type,clients.name
                                  FROM jarviz_io.dbo.billing
                                  INNER JOIN jarviz_io.dbo.clients
                                  ON billing.client_id = clients.id
                                  WHERE client_id = :client_id");

    $sth->bindValue(':client_id', $client);
    $sth->execute();
    /** @var Array $billings */
    $billings = array();
    while ($billing = $sth->fetchObject('Jarviz\DB\Billing'))
      $billings[] = $billing;
    /** @var Array _result */
    $this->_result = $billings;

      return $this->_result;
  }

  /**
   * Создает новый биллинг
   *
   * @param string $created_at
   * @param string $description
   * @param int $client_id
   * @param int $amount
   * @param string $status
   * @param string $payed_at
   * @throws AuthLevelException
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_USER
   */
  public function newBilling($description,$client_id,$amount,$created_at,$status,$payed_at)
  {
    //$this->_application->getCurrentToken()->checkLevel(Token::L_USER);

    /** @var array $client_id */
    $params = [
      'created_at'  => $created_at,
      'description' => $description,
      'client_id'   => $client_id,
      'amount'      => $amount,
      'status'      => $status,
      'payed_at'    => $payed_at,
    ];

    if (!empty($params) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();
      $sth = $dbInstance->prepare("INSERT INTO jarviz_io.dbo.billing (created_at,description,client_id,amount,payed_at,status)
                                                          VALUES (:created_at,:description,:client_id,:amount,:payed_at,:status)");
      /** @var array $params */
      $params = $this->checkDefaultParams($params);

      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }
      if (!$sth->execute())
        throw new JSONableException('Error when adding a billing');
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Обновляет заданный биллинг
   *
   * @param int $id
   * @param string $created_at
   * @param string $description
   * @param int $client_id
   * @param int $amount
   * @param string $status
   * @param string $payed_at
   * @throws AuthLevelException
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_ADMIN
   */
  public function updateBilling($id, $created_at,$description,$client_id,$amount,$status,$payed_at)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);

    /** @var array $params */
    $params = [
      'created_at'  => $created_at,
      'description' => $description,
      'client_id'   => $client_id,
      'amount'      => $amount,
      'status'      => $status,
      'payed_at'    => $payed_at,
    ];

    if (!empty($params) && !empty($id) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();
      $sth = $dbInstance->prepare("UPDATE jarviz_io.dbo.billing
                                        SET description = :description,
                                        client_id = :client_id,
                                        amount = :amount,
                                        status = :status,
                                        payed_at = :payed_at WHERE id = :id");
      /** @var Array $params */
      $params = $this->checkDefaultParams($params);
      $params[] = $id;

      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }
      if (!$sth->execute())
        throw new JSONableException('Error when updating a billing');
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Фильтрует биллинги по заданным параметрам
   *
   * @param null $client
   * @param string $status
   * @param datetime $payed_at
   * @param int $type
   * @param datetime $from
   * @param datetime $to
   * @throws AuthLevelException
   * @internal param string $name
   * @internal param datetime $created_at
   * @return array
   */
  public function filter($client = null, $status = null, $payed_at = null, $type = null, $from = null, $to = null)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    $dt = new \DateTime();
    $from = ($from == null) ? "2000-01-01" : $from;
    $to = ($to == null) ? $dt->format('Y-m-d') : $to;

    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $params = [
      'client' => ($client != null) ? 'name = :client' : null,
      'status' => ($status != null) ? 'status = :status' : null,
      'payed_at' => ($payed_at != null) ? 'payed_at = :payed_at' : null,
      'type' => ($type != null) ? 'type = :type' : null,
    ];

    $time = [
      'from' => $from,
      'to'   => $to
    ];

    //Переменная для подставки WHERE в запрос
    $where = ($params['client'] != '' || $params['status'] != '' || $params['payed_at'] != '' ||
      $params['type'] != '' || $time['from'] != '' || $time['to'] != '') ? ' WHERE ' : null;

    $i = 0;
    $string = '';
    $state = false;
    //Цикл для подставки кусков запроса в переменную для сборки запроса
    foreach($params as $key => $value) {
      if($value != null) {
        $state = true;
        if($i == 0) {
          //Если это первый элемент после WHERE то перед ним AND не ставим
          $string = $value;
          $i++;
        } else {
          //Если не первый, то ставим запятую
          $string .= ' AND '.$value;
        }
      }


    }
    $and = ($state == true) ? ' AND ' : null;

    if($time['from'] != null && $time['to'] != null) {
      $string = $string.$and."billing.payed_at >= :from AND billing.payed_at <= :to";
    }

    $query = "SELECT billing.id AS bil_id,description,client_id,amount,billing.created_at AS bil_created_at,status,payed_at,type,clients.name FROM jarviz_io.dbo.billing INNER JOIN jarviz_io.dbo.clients ON billing.client_id = clients.id ";

    $sth = $dbInstance->prepare($query.$where.$string);

    //var_dump($sth);
    //Если есть параметры для фильтрации то подставляем их в запрос
    if($string != '') {
      foreach($params as $key => $value) {
        if ($value != null) {
          $sth->bindValue($key, $$key);
        }
      }
    }

    $sth->bindValue(':from', $from);
    $sth->bindValue(':to', $to);

    $sth->execute();
    $billings = array();

    while($billing = $sth->fetchObject('Jarviz\DB\Billing'))
      $billings[] = $billing;

    $this->_result = $billings;

    return $billings;
    //Возвращаем в API controller данные для вывода в JSON
  }


}