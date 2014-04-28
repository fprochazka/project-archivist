<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\Security\Role;
use Archivist\Security\UserContext;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Writer extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;



	public function __construct(Kdyby\Doctrine\EntityManager $em, UserContext $user)
	{
		$this->em = $em;
		$this->user = $user;
	}



	/**
	 * @param Question $question
	 * @param Category $category
	 * @return Question
	 */
	public function askQuestion(Question $question, Category $category)
	{
		$question->category = $category;
		$question->setAuthor($this->user->getIdentity());

		$this->em->persist($question)->flush();

		return $question;
	}



	public function answerQuestion(Answer $answer, Question $question)
	{
		if ($question->isLocked() && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ThreadLockedException;
		}

		$question->addAnswer($answer);
		$answer->setAuthor($this->user->getIdentity());

		$this->em->persist($answer)->flush();

		return $answer;
	}



	public function toggleResolvedBy(Question $question, Answer $answer)
	{
		if (!$question->isAuthor($this->user->getIdentity()) && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		if ($question->isLocked() && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ThreadLockedException;
		}

		if ($question->solution === $answer) {
			$question->solution = NULL;

		} else {
			$question->setSolution($answer);
		}

		return $question;
	}



	public function markAsDeleted(Question $question, Post $post)
	{
		if (!$post->isAuthor($this->user->getIdentity()) && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException;
		}

		if ($question->isLocked() && !$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		$post->setSpam(TRUE);
	}



	public function markAsSpam(Question $question, Post $post)
	{
		if (!$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		$post->setSpam(TRUE);
	}



	public function togglePinned(Question $question)
	{
		if (!$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		$question->setPinned(!$question->isPinned());

		return $question;
	}



	public function toggleLocked(Question $question)
	{
		if (!$this->user->isInRole(Role::MODERATOR)) {
			throw new ModificationsNotAllowedException();
		}

		$question->setLocked(!$question->isLocked());

		return $question;
	}

}
