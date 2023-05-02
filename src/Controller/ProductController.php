<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_product', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        //Récupére tous les produits
        $products = $em->getRepository(Product::class)->findAll();
        return new JsonResponse($products);
    }

    //READ
    #[Route('/product/{id}', name: 'one_product', methods: ['GET'])]
    public function show($id, Product $product = null, EntityManagerInterface $em): Response
    {
        $product = $em->getRepository(Product::class)->findOneById($id);
        if ($product === null) {
            return new JsonResponse('Produit introuvable', 404); //retourne un status 404 car le 204 ne retourne pas de message
        }
        return new JsonResponse($product);
    }

    //ADD
    #[Route('/product', name: 'product_add', methods: ['POST'])]
    public function add(EntityManagerInterface $em, Request $request, Validator $validator, Product $product): Response
    {
        if ($product == null) {
            return new JsonResponse('Produit introuvable', 200);
        }

        if ($request->get('category') != null) {
            //Récupere en base la catégorie qui correspond au paramétre reçu
            $category = $em->getRepository(Category::class)->find($request->get('category'));

            //Si elle n'existe pas 
            if ($category == null) {
                return new JsonResponse('Catégorie introuvable', 404);
            }

            //Si elle existe, on l'assigne à l'objet
            $product->setCategory($category);
        }

        if ($request->get('title') != null) {
            $product->setTitle($request->get('title')); //récupére le paramétre 'title' de la requête et l'assigne de l'objet
        }
        if ($request->get('price') != null) {
            $product->setPrice($request->get('price'));
        }

        if ($request->get('quantity') != null) {
            $product->setQuantity($request->get('quantity'));
        }

        if ($request->get('category') != null) {
            $product->setCategory($category);
        }

        //Faire les vérifications
        $isValid = $validator->isValid($product);
        if ($isValid !== true) {
            return new JsonResponse($isValid, 400);
        }

        $em->persist($product); //prépare l'insertion en base
        $em->flush(); //execute l'insertion en base

        return new JsonResponse('ok', 200);
    }

    //UPDATE
    #[Route('/product/{id}', name: 'product_update', methods: ['PATCH'])]
    public function update(Product $product = null, Request $request, Validator $validator, EntityManagerInterface $em)
    {
        if ($product === null) {
            return new JsonResponse('Produit introuvable', 404);
        }
        $params = 0;
        if ($request->get('title') !== null) {
            $params++; // retourne erreur personnnaliser si aucune erreur retourner
            $product->setTitle($request->get('title'));
            $params++;
        }
        if ($request->get('price') !== null) {
            $params++;
            $product->setPrice($request->get('price'));
            $params++;
        }
        if ($request->get('quantity') !== null) {
            $params++;
            $product->setQuantity($request->get('quantity'));
            $params++;
        }
        if ($request->get('category') !== null) {
            $params++;
            $category = $em->getRepository(Category::class)->find($request->get('category'));
            $product->setCategory($category);
            $params++;
        }

        if ($params > 0) {
            //Faire les vérifications
            $isValid = $validator->isValid($product);
            if ($isValid !== true) {
                return new JsonResponse($isValid, 400);
            }

            $em->persist($product); //prépare l'insertion en base
            $em->flush(); //execute l'insertion en base

            return new JsonResponse('Success', 200);
        } else {
            return new JsonResponse('Aucune donnée reçue', 200);
        }
    }

    //DELETE
    #[Route('/product/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete(Product $product = null, EntityManagerInterface $em)
    {
        if ($product === null) {
            return new JsonResponse('Produit introuvable', 404);
        }
        $em->remove($product);
        $em->flush();
        return new JsonResponse('Success', 200);
    }
}
