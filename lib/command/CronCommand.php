<?php

namespace CsrDelft\command;

use CsrDelft\repository\DebugLogRepository;
use CsrDelft\repository\forum\ForumCategorieRepository;
use CsrDelft\repository\instellingen\InstellingenRepository;
use CsrDelft\repository\instellingen\LidInstellingenRepository;
use CsrDelft\repository\LogRepository;
use CsrDelft\repository\security\OneTimeTokensRepository;
use CsrDelft\service\corvee\CorveeHerinneringService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command {
	protected static $defaultName = 'stek:cron';
	/**
	 * @var DebugLogRepository
	 */
	private $debugLogRepository;
	/**
	 * @var LogRepository
	 */
	private $logRepository;
	/**
	 * @var OneTimeTokensRepository
	 */
	private $oneTimeTokensRepository;
	/**
	 * @var InstellingenRepository
	 */
	private $instellingenRepository;
	/**
	 * @var LidInstellingenRepository
	 */
	private $lidInstellingenRepository;
	/**
	 * @var CorveeHerinneringService
	 */
	private $corveeHerinneringService;
	/**
	 * @var ForumCategorieRepository
	 */
	private $forumCategorieRepository;
	/**
	 * @var PinTransactiesDownloadenCommand
	 */
	private $pinTransactiesDownloadenCommand;

	protected function configure() {
		$this
			->setDescription('Voer alle periodieke taken uit');
	}

	public function __construct(
		PinTransactiesDownloadenCommand $pinTransactiesDownloadenCommand,
		DebugLogRepository $debugLogRepository,
		LogRepository $logRepository,
		OneTimeTokensRepository $oneTimeTokensRepository,
		InstellingenRepository $instellingenRepository,
		LidInstellingenRepository $lidInstellingenRepository,
		CorveeHerinneringService $corveeHerinneringService,
		ForumCategorieRepository $forumCategorieRepository
	) {
		parent::__construct(null);
		$this->debugLogRepository = $debugLogRepository;
		$this->logRepository = $logRepository;
		$this->oneTimeTokensRepository = $oneTimeTokensRepository;
		$this->instellingenRepository = $instellingenRepository;
		$this->lidInstellingenRepository = $lidInstellingenRepository;
		$this->corveeHerinneringService = $corveeHerinneringService;
		$this->forumCategorieRepository = $forumCategorieRepository;
		$this->pinTransactiesDownloadenCommand = $pinTransactiesDownloadenCommand;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$start = microtime(true);

		$output->writeln("debuglog opschonen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->debugLogRepository->opschonen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', 'debugLogRepository->opschonen', array(), $e);
		}

		$output->writeln("Log opschonen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->logRepository->opschonen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', 'logRepository->opschonen', array(), $e);
		}

		$output->writeln("One time tokens opschonen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->oneTimeTokensRepository->opschonen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', 'oneTimeTokensRepository->opschonen', array(), $e);
		}

		$output->writeln("Instellingen opschonen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->instellingenRepository->opschonen();
			$this->lidInstellingenRepository->opschonen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', '(Lid)InstellingenRepository->opschonen', array(), $e);
		}

		$output->writeln("Corvee herinneringen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->corveeHerinneringService->stuurHerinneringen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', 'corveeHerinneringenService->stuurHerinneringen', array(), $e);
		}

		$output->writeln("Forum opschonen", OutputInterface::VERBOSITY_VERBOSE);
		try {
			$this->forumCategorieRepository->opschonen();
		} catch (Exception $e) {
			$output->writeln($e->getMessage());
			$this->debugLogRepository->log('cron.php', 'forumCategorieRepository->opschonen', array(), $e);
		}

		$ret = $this->getApplication()
			->find(PinTransactiesDownloadenCommand::getDefaultName())
			->run(new ArrayInput(["--no-interaction" => true]), $output);

		if ($ret !== 0) {
			$output->writeln($ret);
			$this->debugLogRepository->log('cron.php', 'pin_transactie_download', [], 'exit ' . $ret);
		}

		$finish = microtime(true) - $start;
		$output->writeln(getDateTime() . ' Finished in ' . (int)$finish . ' seconds', OutputInterface::VERBOSITY_VERBOSE);

		return Command::SUCCESS;
	}
}
