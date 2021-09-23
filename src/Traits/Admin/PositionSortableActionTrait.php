<?php

namespace Adeliom\EasyFieldsBundle\Traits\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait PositionSortableActionTrait {

    public function sortPositionAction(AdminContext $context): Response
    {
        list($leftPrimaryKeyValue, $leftParentProperty) = $context->getRequest()->get('l') ? explode(':', $context->getRequest()->get('l')) : [null, null];
        list($rightPrimaryKeyValue, $rightParentProperty) = $context->getRequest()->get('r') ? explode(':', $context->getRequest()->get('r')) : [null, null];
        list($primaryKeyValue, $parentProperty) = $context->getRequest()->get('c') ? explode(':', $context->getRequest()->get('c')) : [null, null];

        /**
         * @var ContainerInterface $container
         */
        $container = $this->container;
        $leftEntity = $rightEntity = $entity = null;

        if ( !empty($leftPrimaryKeyValue) &&  !empty($leftParentProperty) ) {

            $leftEntity = $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->find($leftPrimaryKeyValue);

        }

        if ( !empty($rightPrimaryKeyValue) &&  !empty($rightParentProperty) ) {

            $rightEntity = $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->find($rightPrimaryKeyValue);

        }

        if ( !empty($primaryKeyValue) &&  !empty($parentProperty) ) {

            $entity = $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->find($primaryKeyValue);

        }

        $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->recover();

        if (!empty($leftEntity)) {
            try {
                if ($leftEntity->{'get' . ucFirst($parentProperty)}() !== $entity->{'get' . ucFirst($parentProperty)}()) {
                    $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->persistAsFirstChildOf($entity, $leftEntity);
                } else {
                    $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->persistAsNextSiblingOf($entity, $leftEntity);
                }
            } catch (\Exception $exception) {
                $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->persistAsFirstChildOf($entity, $leftEntity);
            }
        } else if (!empty($rightEntity)) {
            try {
                $container->get("doctrine")->getRepository($context->getEntity()->getFqcn())->persistAsPrevSiblingOf($entity, $rightEntity);
            } catch (\Exception $exception) {
                $container->get("doctrine")->getRepository( $context->getEntity()->getFqcn() )->persistAsNextSiblingOf($rightEntity, $entity);
            }
        }

        /**
         * @var Doctrine\Bundle\DoctrineBundle\Registry $doctrine
         */
        $doctrine = $container->get("doctrine");
        $doctrine->getManagerForClass( $context->getEntity()->getFqcn() )->flush();

        return new JsonResponse(['redirectTo' => null]);
    }

}
