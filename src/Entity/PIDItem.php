<?php
namespace PersistentIdentifiers\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * @Entity
 */
class PidItem extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     * @var int
     */
    protected $item;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $pid;

    public function getId()
    {
        return $this->id;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setPID($pid)
    {
        $this->pid = $pid;
    }

    public function getPID()
    {
        return $this->pid;
    }
}
