<?php
declare(strict_types=1);

namespace K3ssen\GeneratorBundle\Controller;

use K3ssen\GeneratorBundle\Entity\MetaEntity;
use K3ssen\GeneratorBundle\Form\MetaEntityType;
use K3ssen\GeneratorBundle\Generator\EntityReader;
use K3ssen\GeneratorBundle\Generator\EntityGenerator;
use K3ssen\GeneratorBundle\Repository\MetaEntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/meta-entity")
 */
class MetaEntityController extends AbstractController
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @Route("/", name="generator_meta_entity_index", methods={"GET"})
     */
    public function index(MetaEntityRepository $metaEntityRepository): Response
    {
        return $this->render('@Generator/meta_entity/index.vue.twig', [
            'meta_entities' => $metaEntityRepository->findAll(),
            'entities' => $this->getEntities(),
        ]);
    }

    protected function getEntities(): array
    {
        $entityDir = $this->projectDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity';
        $finder = new Finder();
        /** @var \SplFileInfo $file */
        $entityNames = [];
        foreach ($finder->in($entityDir)->contains('@ORM\Entity') as $file ) {
            $entityNames[] = str_replace('.php', '', $file->getFilename());
        }
        return $entityNames;
    }

    /**
     * @Route("/new/{entityName}", name="generator_meta_entity_new", methods={"GET","POST"})
     */
    public function new(Request $request, ?string $entityName = null): Response
    {
        $metaEntity = $entityName ? EntityReader::readEntity($entityName) : new MetaEntity();
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

            if ($request->get('generate')) {
                return $this->redirectToRoute('generator_meta_entity_generate', ['id' => $metaEntity->getId()]);
            }

            return $this->redirectToRoute('generator_meta_entity_edit', ['id' => $metaEntity->getId()]);
        }

        if (class_exists($metaEntity->getClass())) {
            $this->addFlash('warning', sprintf('The entity "%s" already exists. This entity will be overwritten if you choose to generate this form.', $metaEntity->getName()));
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
