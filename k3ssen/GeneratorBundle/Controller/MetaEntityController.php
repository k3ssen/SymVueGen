<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Controller;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use K3ssen\GeneratorBundle\Form\MetaEntityType;
use K3ssen\GeneratorBundle\Generator\EntityReader;
use K3ssen\GeneratorBundle\Generator\EntityGenerator;
use K3ssen\GeneratorBundle\Repository\MetaEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/meta-entity")
 */
class MetaEntityController extends AbstractController
{
    /**
     * @Route("/", name="generator_meta_entity_index", methods={"GET"})
     */
    public function index(MetaEntityRepository $metaEntityRepository): Response
    {
        return $this->render('@Generator/meta_entity/index.vue.twig', [
            'meta_entities' => $metaEntityRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="generator_meta_entity_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityReader $classToMetaEntityReader): Response
    {
        $metaEntity = new MetaEntity();
        $form = $this->createForm(MetaEntityType::class, $metaEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($metaEntity);
            $entityManager->flush();

            return $this->redirectToRoute('generator_meta_entity_index');
        }
        return $this->render('@Generator/meta_entity/edit.vue.twig', [
            'meta_entity' => $metaEntity,
            'form' => $form->createView(),
        ]);
    }
//
//    /**
//     * @Route("/{id}", name="meta_entity_show", methods={"GET"})
//     */
//    public function show(MetaEntity $metaEntity): Response
//    {
//        return $this->render('meta_entity/show.html.twig', [
//            'meta_entity' => $metaEntity,
//        ]);
//    }

    /**
     * @Route("/{id}/edit", name="generator_meta_entity_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, MetaEntity $metaEntity, EntityGenerator $entityGenerator): Response
    {
        $form = $this->createForm(MetaEntityType::class, $metaEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'saved successfully');

            return $this->redirectToRoute('generator_meta_entity_edit', ['id' => $metaEntity->getId()]);
        }

        return $this->render('@Generator/meta_entity/edit.vue.twig', [
            'meta_entity' => $metaEntity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/generate", name="generator_meta_entity_generate", methods={"GET","POST"})
     */
    public function generate(MetaEntity $metaEntity, EntityGenerator $entityGenerator): Response
    {
        $entityGenerator->createEntity($metaEntity);
        $this->addFlash('success', 'Generated');
        return $this->redirectToRoute('generator_meta_entity_edit', ['id' => $metaEntity->getId()]);
    }

    /**
     * @Route("/{id}", name="generator_meta_entity_delete", methods={"DELETE"})
     */
    public function delete(Request $request, MetaEntity $metaEntity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$metaEntity->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($metaEntity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('generator_meta_entity_index');
    }
}
