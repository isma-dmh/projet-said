<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[Route('/api/admin/invite-user', name: 'app_invite_user', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function inviteUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $firstname = $data['firstname'] ?? null;
        $lastname = $data['lastname'] ?? null;
        $roles = $data['roles'] ?? ['ROLE_ADMIN'];

        if (!$email || !$firstname || !$lastname) {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setRoles($roles);
        $user->setIsActive(false);

        $token = bin2hex(random_bytes(32));
        $user->setInvitationToken($token);
        $user->setInvitationExpiresAt(new \DateTimeImmutable('+48 hours'));

        $this->em->persist($user);
        $this->em->flush();

        $activationUrl = 'http://localhost:3000/activate-account?token=' . $token;

        $mail = (new Email())
            ->from('no-reply@said.be')
            ->to($email)
            ->subject('Invitation à créer votre compte')
            ->html("Bonjour {$firstname},<br><br>Vous avez été invité(e) à créer un compte. <a href='{$activationUrl}'>Cliquez ici pour définir votre mot de passe</a>.<br><br>Ce lien expire dans 48 heures.");

        $this->mailer->send($mail);

        return $this->json(['message' => 'Invitation envoyée', 'email' => $email], 201);
    }

    #[Route('/api/activate-account', name: 'app_activate_account', methods: ['POST'])]
    public function activateAccount(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;

        if (!$token || !$password) {
            return $this->json(['error' => 'Token et mot de passe requis'], 400);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 8 caractères'], 400);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['invitationToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 404);
        }

        if ($user->isActive()) {
            return $this->json(['error' => 'Ce compte est déjà activé'], 409);
        }

        if ($user->getInvitationExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Ce lien a expiré, demandez une nouvelle invitation'], 410);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsActive(true);
        $user->setInvitationToken(null);
        $user->setInvitationExpiresAt(null);

        $this->em->flush();

        return $this->json(['message' => 'Compte activé avec succès']);
    }
}
