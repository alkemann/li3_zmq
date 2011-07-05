<?php

namespace li3_zmq\tests\mocks\models;

class Posts extends \lithium\tests\mocks\data\MockBase {

	protected $_meta = array(
		'key' => 'id',
		'connection' => false,
		'locked' => false
	);

	protected static $_pk = 4;

	public $validates = array(
		'title' => array('alphaNumeric', 'message' => 'Only Alphanumeric')
	);

	public static function find($finder = 'all', array $options = array()) {
		if ($finder == 'all' || is_array($finder)) return static::all($options);
		if ($finder == 'first') return static::first($options);
		if (empty($options)) return static::first(array('conditions' => array(static::key() => $finder)));
		throw new \Exception('No such finder');
	}

	public static function first(array $options = array()) {
		return new \lithium\data\entity\Document(array(
			'model' => __CLASS__,
			'data' => array('id' => 2, 'title' => 'Blue')
		));
	}

	public static function all(array $options = array()) {
		return new \lithium\data\collection\DocumentSet(array(
			'model' => __CLASS__,
			'data' => array(
				array('id' => 1, 'title' => 'Name'),
				array('id' => 2, 'title' => 'Blue'),
				array('id' => 3, 'title' => 'Mountain')
			)
		));
	}

	public function delete($entity, array $options = array()) {
		return true;
	}

	public static function pk() {
		return static::$_pk++;
	}

	public function save($entity, $data = array(), array $options = array()) {
		if (!isset($entity->id)) {
			$entity->id = Posts::pk();
		}
		return true;
	}

}
