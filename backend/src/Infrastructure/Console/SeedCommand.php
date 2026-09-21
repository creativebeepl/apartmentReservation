<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\RegisterUserHandler;
use App\Domain\Entity\Resource;
use App\Domain\Repository\ResourceRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

/** Idempotentne dane startowe: przykładowe apartamenty i (opcjonalnie) konto demo. */
#[AsCommand(name: 'app:seed', description: 'Seeds demo resources and the demo user (idempotent).')]
final class SeedCommand extends Command
{
    private const RESOURCE_NAMES = [
        'Apartament 101',
        'Apartament 102',
        'Apartament 201',
        'Apartament 202',
        'Penthouse',
    ];

    public function __construct(
        private readonly ResourceRepositoryInterface $resources,
        private readonly UserRepositoryInterface $users,
        private readonly RegisterUserHandler $registerUser,
        private readonly string $demoEmail,
        private readonly string $demoPassword,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->resources->count() === 0) {
            foreach (self::RESOURCE_NAMES as $name) {
                $this->resources->add(new Resource(Uuid::v7(), $name));
            }
            $io->writeln(sprintf('Created %d resources.', count(self::RESOURCE_NAMES)));
        }

        if ($this->demoEmail !== '' && $this->demoPassword !== '' && $this->users->findByEmail($this->demoEmail) === null) {
            ($this->registerUser)($this->demoEmail, $this->demoPassword);
            $io->writeln(sprintf('Created demo user %s.', $this->demoEmail));
        }

        return Command::SUCCESS;
    }
}
