<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\RegisterUserHandler;
use App\Application\Auth\UserAlreadyExistsException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:user:create', description: 'Creates an API user.')]
final class CreateUserCommand extends Command
{
    public function __construct(private readonly RegisterUserHandler $registerUser)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User e-mail (login)')
            ->addArgument('password', InputArgument::REQUIRED, 'Password (min. 8 characters)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        if (! is_string($email) || ! is_string($password)) {
            $io->error('E-mail and password are required.');

            return Command::INVALID;
        }

        try {
            $user = ($this->registerUser)($email, $password);
        } catch (\InvalidArgumentException | UserAlreadyExistsException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('User %s created.', $user->email()));

        return Command::SUCCESS;
    }
}
