<?php

namespace CsrDelft\entity\civimelder;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class ActiviteitEigenschappen {
	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $titel;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $beschrijving;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $capaciteit;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rechtenAanmelden;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rechtenLijstBekijken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rechtenLijstBeheren;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxGasten;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $aanmeldenMogelijk;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $aanmeldenVanaf;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $aanmeldenTot;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $afmeldenMogelijk;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $afmeldenTot;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $voorwaarden = [];

    public function getTitel(): ?string {
		return $this->titel;
	}

	public function setTitel(?string $titel): self {
		$this->titel = $titel;

		return $this;
	}

	public function getBeschrijving(): ?string {
		return $this->beschrijving;
	}

	public function setBeschrijving(?string $beschrijving): self {
		$this->beschrijving = $beschrijving;

		return $this;
	}

	public function getCapaciteit(): ?int {
		return $this->capaciteit;
	}

	public function setCapaciteit(?int $capaciteit): self {
		$this->capaciteit = $capaciteit;

		return $this;
	}

	public function getRechtenAanmelden(): ?string {
		return $this->rechtenAanmelden;
	}

	public function setRechtenAanmelden(?string $rechtenAanmelden): self {
		$this->rechtenAanmelden = $rechtenAanmelden;

		return $this;
	}

	public function getRechtenLijstBekijken(): ?string {
		return $this->rechtenLijstBekijken;
	}

	public function setRechtenLijstBekijken(?string $rechtenLijstBekijken): self {
		$this->rechtenLijstBekijken = $rechtenLijstBekijken;

		return $this;
	}

	public function getRechtenLijstBeheren(): ?string {
		return $this->rechtenLijstBeheren;
	}

	public function setRechtenLijstBeheren(?string $rechtenLijstBeheren): self {
		$this->rechtenLijstBeheren = $rechtenLijstBeheren;

		return $this;
	}

	public function getMaxGasten(): ?int {
		return $this->maxGasten;
	}

	public function setMaxGasten(?int $maxGasten): self {
		$this->maxGasten = $maxGasten;

		return $this;
	}

	public function getAanmeldenMogelijk(): ?bool {
		return $this->aanmeldenMogelijk;
	}

	public function isAanmeldenMogelijk(?bool $aanmeldenMogelijk): self {
		$this->aanmeldenMogelijk = $aanmeldenMogelijk;

		return $this;
	}

	public function getAanmeldenVanaf(): ?int {
		return $this->aanmeldenVanaf;
	}

	public function setAanmeldenVanaf(?int $aanmeldenVanaf): self {
		$this->aanmeldenVanaf = $aanmeldenVanaf;

		return $this;
	}

	public function getAanmeldenTot(): ?int {
		return $this->aanmeldenTot;
	}

	public function setAanmeldenTot(?int $aanmeldenTot): self {
		$this->aanmeldenTot = $aanmeldenTot;

		return $this;
	}

	public function isAfmeldenMogelijk(): ?bool {
		return $this->afmeldenMogelijk;
	}

	public function setAfmeldenMogelijk(?bool $afmeldenMogelijk): self {
		$this->afmeldenMogelijk = $afmeldenMogelijk;

		return $this;
	}

	public function getAfmeldenTot(): ?int {
		return $this->afmeldenTot;
	}

	public function setAfmeldenTot(?int $afmeldenTot): self {
		$this->afmeldenTot = $afmeldenTot;

		return $this;
	}

	public function getVoorwaarden(): ?array {
		return $this->voorwaarden;
	}

	public function setVoorwaarden(?array $voorwaarden): self {
		$this->voorwaarden = $voorwaarden;

		return $this;
	}
}