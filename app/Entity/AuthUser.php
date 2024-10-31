<?php

namespace Otys\OtysPlugin\Entity;

class AuthUser
{
    private ?string $firstname;

    private ?string $middleName;

    private ?string $lastname;

    private ?string $email;

    private ?string $gender;

    private string $webuserUid;

    private string $candidateUid;

    private int $candidateId;

    /**
     * OTYS Webuser SESSION id
     *
     * @var string
     */
    private string $sesion;

    public function __construct()
    {
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function getFullName(): string
    {
        $fullName = [
            $this->firstname,
            $this->middleName,
            $this->lastname
        ];

        // Remove null values
        $fullName = array_filter($fullName);

        return implode(' ', $fullName);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getWebuserUid(): string
    {
        return $this->webuserUid;
    }

    public function setWebuserUid(string $webuserUid): void
    {
        $this->webuserUid = $webuserUid;
    }

    public function getCandidateUid(): string
    {
        return $this->candidateUid;
    }

    public function setCandidateUid(string $candidateUid): void
    {
        $this->candidateUid = $candidateUid;
    }

    public function getSesion(): string
    {
        return $this->sesion;
    }

    public function setSesion(string $sesion): void
    {
        $this->sesion = $sesion;
    }

    public function getCandidateId(): int
    {
        return $this->candidateId;
    }

    public function setCandidateId(int $candidateId): void
    {
        $this->candidateId = $candidateId;
    }
}