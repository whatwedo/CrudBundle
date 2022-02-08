<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use whatwedo\CrudBundle\Tests\App\Entity\Category;
use whatwedo\CrudBundle\Tests\App\Factory\CategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class HierarchicalEntityTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreateEntity()
    {
        /** @var Category $category */
        $category = CategoryFactory::createOne([
            'name' => 'Level 1'
        ])->object();

        /** @var Category $subcategory */
        $subcategory = CategoryFactory::createOne([
            'name' => 'Level 2',
            'parent' => $category
        ])->object();

        $this->assertSame($category, $subcategory->getParent());
        $this->assertSame(1, $category->getLevel());
        $this->assertSame('Level 1', $category->getHierarchicalSorting());
        $this->assertSame(2, $subcategory->getLevel());
        $this->assertSame('Level 1Level 2', $subcategory->getHierarchicalSorting());
        $this->assertCount(1, $category->getChildren());


        $subcategory->setName('Level Zwei');
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->assertSame('Level 1Level Zwei', $subcategory->getHierarchicalSorting());


    }

}
