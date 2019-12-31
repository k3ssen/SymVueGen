<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\MetaEntity;
use App\Form\MetaEntityType;
use App\Repository\MetaEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/meta-entity")
 */
class MetaEntityController extends AbstractController
{
    /**
     * @Route("/", name="meta_entity_index", methods={"GET"})
     */
    public function index(MetaEntityRepository $metaEntityRepository): Response
    {
        return $this->render('meta_entity/index.html.twig', [
            'meta_entities' => $metaEntityRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="meta_entity_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $metaEntity = new MetaEntity();
        $form = $this->createForm(MetaEntityType::class, $metaEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($metaEntity);
            $entityManager->flush();

            return $this->redirectToRoute('meta_entity_index');
        }

        return $this->render('meta_entity/new.vue.twig', [
            'meta_entity' => $metaEntity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="meta_entity_show", methods={"GET"})
     */
    public function show(MetaEntity $metaEntity): Response
    {
        return $this->render('meta_entity/show.html.twig', [
            'meta_entity' => $metaEntity,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="meta_entity_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, MetaEntity $metaEntity, SerializerInterface $serializer): Response
    {
        $form = $this->createForm(MetaEntityType::class, $metaEntity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('meta_entity_edit', ['id' => $metaEntity->getId()]);
        }

        return $this->render('meta_entity/edit.html.twig', [
            'meta_entity' => $metaEntity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="meta_entity_delete", methods={"DELETE"})
     */
    public function delete(Request $request, MetaEntity $metaEntity): Response
    {
        if ($this->isCsrfTokenValid('delete'.$metaEntity->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($metaEntity);
            $entityManager->flush();
        }

        return $this->redirectToRoute('meta_entity_index');
    }
}
