<?php

namespace platz1de\EasyEdit\session;

use BadMethodCallException;
use platz1de\EasyEdit\selection\identifier\StoredSelectionIdentifier;
use platz1de\EasyEdit\task\StaticStoredPasteTask;
use platz1de\EasyEdit\thread\input\task\CleanStorageTask;
use SplStack;

class Session
{
	private SessionIdentifier $id;
	/**
	 * @var SplStack<StoredSelectionIdentifier>
	 */
	private SplStack $past;
	/**
	 * @var SplStack<StoredSelectionIdentifier>
	 */
	private SplStack $future;
	/**
	 * @var StoredSelectionIdentifier
	 */
	private StoredSelectionIdentifier $clipboard;

	public function __construct(SessionIdentifier $id)
	{
		if (!$id->isPlayer()) {
			throw new BadMethodCallException("Session can only be created for players, plugins or internal use should use tasks directly");
		}
		$this->id = $id;
		$this->past = new SplStack();
		$this->future = new SplStack();
		$this->clipboard = StoredSelectionIdentifier::invalid();
	}

	/**
	 * @return SessionIdentifier
	 */
	public function getIdentifier(): SessionIdentifier
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getPlayer(): string
	{
		return $this->id->getName();
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 * @param bool                      $fromUndo
	 * @return void
	 */
	public function addToHistory(StoredSelectionIdentifier $id, bool $fromUndo): void
	{
		if ($fromUndo) {
			$this->future->unshift($id);
		} else {
			$this->past->unshift($id);
			if (!$this->future->isEmpty()) {
				CleanStorageTask::from(iterator_to_array($this->future, false));
				$this->future = new SplStack();
			}
		}
	}

	/**
	 * @return bool
	 */
	public function canUndo(): bool
	{
		return !$this->past->isEmpty();
	}

	/**
	 * @return bool
	 */
	public function canRedo(): bool
	{
		return !$this->future->isEmpty();
	}

	/**
	 * @param SessionIdentifier $executor
	 */
	public function undoStep(SessionIdentifier $executor): void
	{
		if ($this->canUndo()) {
			StaticStoredPasteTask::queue($executor, $this->past->shift(), false, true);
		}
	}

	/**
	 * @param SessionIdentifier $executor
	 */
	public function redoStep(SessionIdentifier $executor): void
	{
		if ($this->canRedo()) {
			StaticStoredPasteTask::queue($executor, $this->future->shift(), false);
		}
	}

	/**
	 * @return StoredSelectionIdentifier
	 */
	public function getClipboard(): StoredSelectionIdentifier
	{
		return $this->clipboard;
	}

	/**
	 * @param StoredSelectionIdentifier $id
	 */
	public function setClipboard(StoredSelectionIdentifier $id): void
	{
		if ($this->clipboard->isValid()) {
			CleanStorageTask::from([$this->clipboard]);
		}
		$this->clipboard = $id;
	}
}