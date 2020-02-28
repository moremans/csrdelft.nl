<?php

namespace CsrDelft\repository;

use CsrDelft\entity\ForumPlaatje;
use CsrDelft\view\formulier\uploadvelden\ImageField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * Class ForumPlaatjeRepository
 * @package CsrDelft\repository
 * @method ForumPlaatje|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForumPlaatje|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForumPlaatje[]    findAll()
 * @method ForumPlaatje[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForumPlaatjeRepository extends ServiceEntityRepository {

	public function __construct(ManagerRegistry $registry) {
		parent::__construct($registry, ForumPlaatje::class);
	}

	public function fromUploader(ImageField $uploader, $uid) {
		$plaatje = static::generate();
		$plaatje->maker = $uid;

		$this->getEntityManager()->persist($plaatje);
		$this->getEntityManager()->flush();

		$uploader->opslaan(PLAATJES_PATH, strval($plaatje->id));
		$plaatje->createResized();
		return $plaatje;
	}

	private static function generate() {
		$plaatje = new ForumPlaatje();
		$plaatje->datum_toegevoegd = date_create();
		$plaatje->access_key = bin2hex(random_bytes(16));
		return $plaatje;
	}

	/**
	 * @param $key
	 * @return ForumPlaatje|null
	 */
	public function getByKey($key) {
		if (!self::isValidKey($key)) {
			return null;
		}
		return $this->findOneBy(["access_key" => $key]);

	}

	public static function isValidKey($key) {
		return preg_match('/^[a-zA-Z0-9]{32}$/', $key);
	}

}