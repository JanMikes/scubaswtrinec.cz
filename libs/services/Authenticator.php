<?php

namespace App\Services;

use Nette,
	Nette\Security,
	App,
	App\Database\Entities\UserEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Authenticator extends Nette\Object implements Security\IAuthenticator
{
	/** @var App\Database\Entities\UserEntity */
	private $userEntity;

	/** @var App\Services\HashService */
	private $hashService;


	public function __construct(UserEntity $userEntity, HashService $hashService)
	{
		$this->userEntity = $userEntity;
		$this->hashService = $hashService;
	}


	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($email, $password) = $credentials;
		$user = $this->userEntity->findByemail($email);

		if (!$user) {
			throw new Security\AuthenticationException("User '$email' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($user->password !== $this->hashService->calculateHash($password)) {
			throw new Security\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		$identity = $user->toArray();
		unset($identity["password"]);

		return new Security\Identity($user->id, null, $identity);
	}


	/**
	 * Changes password to $password of user with id $id. Returns number of affected rows or FALSE in case of an error
	 * @param  int
	 * @param  string
	 * @return int|FALSE
	 */
	public function setPassword($id, $password)
	{
		return $this->userEntity->update($id, array(
			"password" => $this->hashService->calculateHash($password),
			"upd_process" => __METHOD__,
		));
	}


	/**
	 * Refreshes user identity
	 * @param  Nette\Security\Identity
	 */
	public function refreshIdentity(Security\Identity $identity){
		$user = $this->userEntity->find($identity->id);
		unset($user->password);
		foreach($user as $key=>$val) {
			$identity->{$key} = $val;
		}
	}

}
