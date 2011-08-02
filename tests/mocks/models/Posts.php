<?php

namespace li3_zmq\tests\mocks\models;

class Posts extends \lithium\tests\mocks\data\MockBase {

	protected $_meta = array(
		'key' => '_id',
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
			'data' => array('_id' => 2, 'title' => 'Blue')
		));
	}

	public static function all(array $options = array()) {
		return new \lithium\data\collection\DocumentSet(array(
			'model' => __CLASS__,
			'data' => array(
				0 => new \lithium\data\entity\Document(array(
					'model' => __CLASS__,
					'data' => array('_id' => 11, 'title' => 'Name')
				)),
				1 => new \lithium\data\entity\Document(array(
					'model' => __CLASS__,
					'data' => array('_id' => 12, 'title' => 'Blue')
				)),
				2 => new \lithium\data\entity\Document(array(
					'model' => __CLASS__,
					'data' => array('_id' => 13, 'title' => 'Mountain')
				))
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
		$data = $data ?: array();
		$entity->set($data);
		$valid = $entity->validates();
		if ($valid) {
			$pk = static::meta('key');
			if (!isset($entity->$pk)) {
				$entity->$pk = Posts::pk();
			}
		}
		return $valid;
	}

}
