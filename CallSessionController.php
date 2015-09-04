<?php


namespace Jarviz\Controller;


use Jarviz\Auth\Token;
use Jarviz\DB\DBSets;
use Jarviz\Exception\AuthLevelException;
use Jarviz\Exception\JSONableException;

/**
 * Class CallSessionController
 * Данный класс содержит в себе методы для управления сеансами звонков и получения информации о них
 * @package Jarviz\Controller
 */
class CallSessionController extends ApiController
{
  private static $defaultParams = [
    'client_id'        => 0,
    'widget_id'        => 0,
    'status'           => 0,
    'geo_country'      => 'none',
    'geo_city'         => 'none',
    'ip'               => 'none',
    'page'             => 'none',
    'uri'              => 'none',
    'domain'           => 'none',
    'init_at'          => null,
    'amount'           => 0,
    'record_link'      => 'none',
    'ans_operator'     => 'none',
    'ans_operator_num' => 'none',
    'client_num'       => 'none',
    'src_ref'          => 0,
    'utm_source'       => 'none',
    'utm_name'         => 'none',
    'utm_medium'       => 'none',
    'utm_term'         => 'none',
    'utm_content'      => 'none',
    'utm_hash'         => 'none',
    'os'               => 'none',
    'ua'               => 'none',
    'browser'          => 'none',
    'is_mobile'        => 'none',
    'trafic_hash'      => 0
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
    }
    /** Проверяем, если в параметры передано больше значений чем нужно, показываем ошибку */
    if (count($params) > count(self::$defaultParams)) {
      throw new JSONableException('Wrong params');
    }

    return $params;
  }

  /**
   * Возвращает все данные о сеансе вызова по текущему пользователю
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER
   */
  public function getAll()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare('SELECT * FROM jarviz_io.dbo.call_session WHERE client_id = :client_id');
    $sth->bindValue(':client_id', $this->_application->getCurrentClient());
    $sth->execute();
    /** @var Array $callSessions */
    $callSessions = array();
    while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
      $callSessions[] = $callSession;
    /** @var Array _result */
    $this->_result = $callSessions;
  }

  /**
   * Возвращает все данные о сеансе вызова
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_ADMIN
   */
  public function getAllAdmin()
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.call_session");
    $sth->execute();
    /** @var Array $callSessions */
    $callSessions = array();
    while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
      $callSessions[] = $callSession;
    /** @var Array _result */
    $this->_result = $callSessions;
  }

  /**
   * Возвращает все данные о сеансе вызова по заданому клиенту
   * @min_level L_USER_PARTNER
   * @param int $client
   * @throws \Jarviz\Exception\AuthLevelException
   */
  public function getByClient($client)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER_PARTNER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    //$sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.call_session WHERE client_id = :client_id");

    // Change query
    $sth = $dbInstance->prepare("SELECT call_session.id,
                                    call_session.init_at,
                                    clients.name,
                                    call_session.client_id,
                                    call_session.status,
                                    call_session.client_num,
                                    call_session.ans_operator_num
                                FROM jarviz_io.dbo.call_session AS call_session
                                INNER JOIN jarviz_io.dbo.clients AS clients
                                ON call_session.client_id = clients.id
                                WHERE client_id = :client_id");


    $sth->bindValue(':client_id', $client);
    $sth->execute();

    /** @var Array $callSessions */
    $callSessions = array();
    while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
      $callSessions[] = $callSession;
    /** @var Array _result */
    $this->_result = $callSessions;

    return $this->_result;
  }

  /**
   * Возвращает все данные о сессии звонка по заданому виджету
   * @param int $widget
   * @throws \Jarviz\Exception\AuthLevelException
   * @min_level L_USER
   */
  public function getByWidget($widget)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();
    $sth = $dbInstance->prepare("SELECT * FROM jarviz_io.dbo.call_session WHERE widget_id = :widget_id");
    $sth->bindValue(':widget_id', $widget);
    $sth->execute();
    /** @var Array $callSessions */
    $callSessions = array();
    while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
      $callSessions[] = $callSession;
    /** @var Array _result */
    $this->_result = $callSessions;
  }

  /**
   * Создает новую сессию вызова
   * @param int $client_id
   * @param int $widget_id
   * @param int $status
   * @param string $geo_country
   * @param string $geo_city
   * @param string $ip
   * @param string $page
   * @param string $uri
   * @param string $domain
   * @param int $amount
   * @param string $record_link
   * @param int $ans_operator
   * @param string $ans_operator_num
   * @param string $client_num
   * @param int $src_ref
   * @param string $utm_source
   * @param string $utm_name
   * @param string $utm_medium
   * @param string $utm_term
   * @param string $utm_content
   * @param string $utm_hash
   * @param string $os
   * @param string $ua
   * @param string $browser
   * @param string $is_mobile
   * @param int $trafic_hash
   * @param string $init_at
   * @throws AuthLevelException
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_USER
   */
  public function newCallSession($client_id,$widget_id,$status,$geo_country,$geo_city,$ip,$page,$uri,$domain,$amount,$record_link,$ans_operator,$ans_operator_num,$client_num,$src_ref,$utm_source,$utm_name,$utm_medium,$utm_term,$utm_content,$utm_hash,$os,$ua,$browser,$is_mobile,$trafic_hash,$init_at)
  {
    //$this->_application->getCurrentToken()->checkLevel(Token::L_USER);
    /** @var array $params */
    $params = [
      'client_id'        => $client_id,
      'widget_id'        => $widget_id,
      'status'           => $status,
      'geo_country'      => $geo_country,
      'geo_city'         => $geo_city,
      'ip'               => $ip,
      'page'             => $page,
      'uri'              => $uri,
      'domain'           => $domain,
      'init_at'          => $init_at,
      'amount'           => $amount,
      'record_link'      => $record_link,
      'ans_operator'     => $ans_operator,
      'ans_operator_num' => $ans_operator_num,
      'client_num'       => $client_num,
      'src_ref'          => $src_ref,
      'utm_source'       => $utm_source,
      'utm_name'         => $utm_name,
      'utm_medium'       => $utm_medium,
      'utm_term'         => $utm_term,
      'utm_content'      => $utm_content,
      'utm_hash'         => $utm_hash,
      'os'               => $os,
      'ua'               => $ua,
      'browser'          => $browser,
      'is_mobile'        => $is_mobile,
      'trafic_hash'      => $trafic_hash,
    ];

    if (!empty($params) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();
      $sth = $dbInstance->prepare("INSERT INTO jarviz_io.dbo.call_session (client_id, widget_id, status, geo_country,
                                   geo_city, ip, page, uri, domain, init_at, amount, record_link, ans_operator,
                                   ans_operator_num, client_num, src_ref, utm_source, utm_name, utm_medium, utm_term,
                                   utm_content, utm_hash, os, ua, browser, is_mobile, trafic_hash)
                                   VALUES (:client_id, :widget_id, :status, :geo_country, :geo_city, :ip, :page, :uri,
                                   :domain, :init_at, :amount, :record_link, :ans_operator, :ans_operator_num,
                                   :client_num, :src_ref, :utm_source, :utm_name, :utm_medium, :utm_term,
                                   :utm_content, :utm_hash, :os, :ua, :browser, :is_mobile, :trafic_hash)");
      /** @var Array $params */
      $params = $this->checkDefaultParams($params);

      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }
      if (!$sth->execute()) {
        $this->_result = false;
        throw new JSONableException('Error when adding new call session');
      } else {
        $this->_result = true;;
      }
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Обновляет данные о сессии вызова
   * @param int $id
   * @param int $client_id
   * @param int $widget_id
   * @param int $status
   * @param string $geo_country
   * @param string $geo_city
   * @param string $ip
   * @param string $page
   * @param string $uri
   * @param string $domain
   * @param int $amount
   * @param string $record_link
   * @param int $ans_operator
   * @param string $ans_operator_num
   * @param string $client_num
   * @param int $src_ref
   * @param string $utm_source
   * @param string $utm_name
   * @param string $utm_medium
   * @param string $utm_term
   * @param string $utm_content
   * @param string $utm_hash
   * @param string $os
   * @param string $ua
   * @param string $browser
   * @param string $is_mobile
   * @param int $trafic_hash
   * @param string $init_at
   * @throws AuthLevelException
   * @throws JSONableException
   * @internal param array $params
   * @min_level L_ADMIN
   */
  public function updateCallSession($id, $client_id,$widget_id,$status,$geo_country,$geo_city,$ip,$page,$uri,$domain,$amount,$record_link,$ans_operator,$ans_operator_num,$client_num,$src_ref,$utm_source,$utm_name,$utm_medium,$utm_term,$utm_content,$utm_hash,$os,$ua,$browser,$is_mobile,$trafic_hash,$init_at)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_ADMIN);
    /** @var array $params */
    $params = [
      'client_id'        => $client_id,
      'widget_id'        => $widget_id,
      'status'           => $status,
      'geo_country'      => $geo_country,
      'geo_city'         => $geo_city,
      'ip'               => $ip,
      'page'             => $page,
      'uri'              => $uri,
      'domain'           => $domain,
      'init_at'          => $init_at,
      'amount'           => $amount,
      'record_link'      => $record_link,
      'ans_operator'     => $ans_operator,
      'ans_operator_num' => $ans_operator_num,
      'client_num'       => $client_num,
      'src_ref'          => $src_ref,
      'utm_source'       => $utm_source,
      'utm_name'         => $utm_name,
      'utm_medium'       => $utm_medium,
      'utm_term'         => $utm_term,
      'utm_content'      => $utm_content,
      'utm_hash'         => $utm_hash,
      'os'               => $os,
      'ua'               => $ua,
      'browser'          => $browser,
      'is_mobile'        => $is_mobile,
      'trafic_hash'      => $trafic_hash,
    ];

    if (!empty($id) && !empty($params) && is_array($params)) {
      /** @var \PDO $dbInstance */
      $dbInstance = DBSets::getInstance()->getConnection();
      $sth = $dbInstance->prepare('UPDATE jarviz_io.dbo.call_session
                                            SET client_id = :client_id,
                                            widget_id = :widget_id,
                                            status = :status,
                                            geo_country = :geo_country,
                                            geo_city = :geo_city,
                                            ip = :ip,
                                            page = :page,
                                            uri = :uri,
                                            domain = :domain,
                                            init_at = :init_at,
                                            amount = :amount,
                                            record_link = :record_link,
                                            ans_operator = :ans_operator,
                                            ans_operator_num = :ans_operator_num,
                                            client_num = :client_num,
                                            src_ref = :src_ref,
                                            utm_source = :utm_source,
                                            utm_name = :utm_name,
                                            utm_medium = :utm_medium,
                                            utm_term = :utm_term,
                                            utm_content = :utm_content,
                                            utm_hash = :utm_hash,
                                            os = :os,
                                            ua = :ua,
                                            browser = :browser,
                                            is_mobile = :is_mobile,
                                            trafic_hash = :trafic_hash WHERE id = :id');
      /** @var Array $params */
      $params = $this->checkDefaultParams($params);
      $params[] = $id;

      foreach($params as $key => $value) {
        $sth->bindValue($key,$value);
      }
      if (!$sth->execute()) {
        $this->_result = false;
        throw new JSONableException('Error when updating call session');
      } else {
        $this->_result = true;
      }
    } else {
      throw new JSONableException('Invalid arguments');
    }
  }

  /**
   * Фильтрует данные по звонкам
   *
   * @param int $client_id
   * @param string $client_num
   * @param string $created_from
   * @param string $created_to
   * @return array
   */
  public function filter($client = null, $client_num = null, $ans_operator_num = null, $created_from = null,
                         $created_to = null, $type = null)
  {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);

    $dt = new \DateTime();
    $created_from = ($created_from == null) ? "2000-01-01" : $created_from;
    $created_to = ($created_to == null) ? $dt->format('Y-m-d') : $created_to;
    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();

    $params = [
      'client' => ($client != null) ? 'clients.name = :client' : null,
      'ans_operator_num' => ($ans_operator_num != null) ? 'call_session.ans_operator_num = :ans_operator_num' : null,
      'client_num' => ($client_num != null) ? 'call_session.client_num = :client_num' : null,
    ];

    $time = [
      'from' => $created_from,
      'to' => $created_to
    ];
    //Переменная для подставки WHERE в запрос
    $where = ($params['name'] != '' || $params['status'] != '' || $params['payed_at'] != '' ||
      $params['created_at'] != '' || $params['ans_operator_num'] || $params['type'] != '' || $time['from'] != '' ||
      $time['to'] != '') ? 'WHERE ' : null;

    $i = 0;
    $string = '';
    $state = false;
    //Цикл для подставки кусков запроса в переменную для сборки запроса
    foreach ($params as $key => $value) {
      if ($value != null) {
        $state = true;
        if ($i == 0) {
          //Если это первый элемент после WHERE то перед ним AND не ставим
          $string = $value;
          $i++;
        } else {
          //Если не первый, то ставим запятую
          $string .= ' AND ' . $value;
        }
      }


    }
    $and = ($state == true) ? ' AND ' : null;

    if ($time['from'] != null && $time['to'] != null) {
      $string = $string . $and . 'call_session.init_at >= :from AND call_session.init_at <= :to';
    }

    // Old query
    //$query = "SELECT * FROM jarviz_io.dbo.call_session INNER JOIN jarviz_io.dbo.clients ON call_session.client_id = clients.id ";

    // New query
    $query = "SELECT call_session.id,
                  call_session.init_at,
                  clients.name,
                  call_session.client_id,
                  call_session.status,
                  call_session.client_num,
                  call_session.ans_operator_num
              FROM jarviz_io.dbo.call_session AS call_session
              INNER JOIN jarviz_io.dbo.clients AS clients
              ON call_session.client_id = clients.id ";

    $sth = $dbInstance->prepare($query . $where . $string);
    //var_dump($sth);
    //Если есть параметры для фильтрации то подставляем их в запрос
    if ($string != '') {
      foreach ($params as $key => $value) {
        if ($value != null) {
          $sth->bindValue($key, $$key);
        }
      }
    }

    $sth->bindValue('from', $created_from);
    $sth->bindParam('to', $created_to);

    $sth->execute();

    $callSessions = array();

    while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
      $callSessions[] = $callSession;

    return $callSessions;
    //Возвращаем в API controller данные для вывода в JSON

  }

  /**
   * Получить информацию о звонке по id сессии
   *
   * @param int $call
   */
  public function getCallInfoByID($call) {
    $this->_application->getCurrentToken()->checkLevel(Token::L_USER);

    /** @var \PDO $dbInstance */
    $dbInstance = DBSets::getInstance()->getConnection();


    // Extracting last call_end
    $query = "SELECT call_session.id,
                  call_session.init_at,
                  call_filter_operator.operator_amount,
                  call_filter_client.client_amount,
                  call_time_end.call_end,
                  call_session.ans_operator_num,
                  call_session.client_num,
                  call_session.utm_source,
                  call_session.utm_name,
                  call_session.utm_content,
                  call_session.utm_medium,
                  call_session.utm_term,
                  call_session.record_link
          FROM jarviz_io.dbo.call_session AS call_session
              INNER JOIN (SELECT
                        call.call_session_id AS call_operator_session_id,
                        call.phone_number AS call_operator_num,
                        SUM (call.amount) AS operator_amount
                  FROM jarviz_io.dbo.call AS call
                    INNER JOIN jarviz_io.dbo.call_session AS call_session
                      ON call.call_session_id = call_session.id
                        AND call.phone_number = call_session.ans_operator_num
                  WHERE call_session.id = :id1
                  GROUP BY call.phone_number, call.call_session_id)
                AS call_filter_operator
                ON call_session.id = call_filter_operator.call_operator_session_id
              INNER JOIN (SELECT
                          call.call_session_id AS call_client_session_id,
                          call.phone_number AS call_client_num,
                          SUM (call.amount) AS client_amount
                      FROM jarviz_io.dbo.call AS call
                      INNER JOIN jarviz_io.dbo.call_session AS call_session
                        ON call.call_session_id = call_session.id
                          AND call.phone_number = call_session.client_num
                      WHERE call_session.id = :id2
                      GROUP BY call.phone_number, call.call_session_id)
                AS call_filter_client
                ON call_session.id = call_filter_client.call_client_session_id
              INNER JOIN (SELECT T.id,
	                          T.call_session_id,
	                          T.phone_number,
	                          T.call_end
	                      FROM (SELECT
		                            call.id,
		                            call.call_session_id,
		                            call.phone_number,
		                            call.call_end,
		                            ROW_NUMBER() OVER(
		                            PARTITION BY call.call_session_id
		                            ORDER BY call.call_end DESC) AS rownumb
                            FROM jarviz_io.dbo.call AS call
                            WHERE call.call_session_id = :id3) AS T
                      WHERE T.rownumb = 1)
                AS call_time_end
                ON call_session.id = call_time_end.call_session_id
          WHERE call_session.id= :id";

    $sth = $dbInstance->prepare($query);

    // Don't use the same :id in query, or SQL Server will return error
    $sth->bindValue(':id', $call);
    $sth->bindValue(':id1', $call);
    $sth->bindValue(':id2', $call);
    $sth->bindValue(':id3', $call);
    $sth->execute();

    /** @var Array $callSessions */
    $callSessions = array();
    //while ($callSession = $sth->fetchObject('Jarviz\DB\CallSession'))
    while ($callSession = $sth->fetch(\PDO::FETCH_ASSOC))
      $callSessions[] = $callSession;

    return $callSessions;
  }
}