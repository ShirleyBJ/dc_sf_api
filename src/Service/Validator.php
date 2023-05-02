<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 *  param $obj prend un objet en paramétre
 */
class Validator {

    private $v;

    public function __construct(ValidatorInterface $validator){
        $this->v = $validator;
    }

    public function isValid($obj){
        $errors = $this->v->validate($obj); //vérifie que l'objet soit conforme avec les validations demandées(assert)
        
        if (count($errors) > 0) {
            $e_list = [];
            //S'il y a au moins une erreur
            foreach ($errors as $error) {
                $e_list[] = $error->getMessage(); //on ajoute leur message dans le tableau
            }
            return $e_list; //on retourne le tableau des messages
        } else{
            return True;
        }
    }
}
