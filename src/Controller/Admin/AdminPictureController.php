<?php
namespace App\Controller\Admin;

use App\Entity\Picture;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/picture")
 */
class AdminPictureController extends AbstractController {

    /**
     * @Route("/{id}", name="admin.picture.delete", methods="DELETE")
     */
    public function delete(Picture $picture, Request $request)
    {
        $propertyId = $picture->getProperty()->getId();

        if ($this->isCsrfTokenValid('delete'.$picture->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($picture);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin.property.edit', ['id' => $propertyId]);
    }
}
