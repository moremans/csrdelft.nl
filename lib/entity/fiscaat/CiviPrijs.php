<?php

namespace CsrDelft\entity\fiscaat;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CiviPrijs
 *
 * Prijs van een @see CiviProduct van en tot zorgen ervoor dat altijd terug te vinden is wat de prijs van een product
 * was op een bepaald moment.
 *
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @ORM\Entity(repositoryClass="CsrDelft\repository\fiscaat\CiviPrijsRepository")
 * @ORM\Table("CiviPrijs")
 */
class CiviPrijs {
	/**
	 * @var integer
	 * @ORM\Column(type="integer")
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 */
	public $id;
	/**
	 * @var \DateTimeImmutable
	 * @ORM\Column(type="datetime")
	 */
	public $van;
	/**
	 * @var \DateTimeImmutable
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	public $tot;
	/**
	 * @var integer
	 * @ORM\Column(type="integer")
	 */
	public $product_id;
	/**
	 * @var CiviProduct
	 * @ORM\ManyToOne(targetEntity="CiviProduct", inversedBy="prijzen")
	 */
	public $product;
	/**
	 * @var integer
	 * @ORM\Column(type="integer")
	 */
	public $prijs;
}
