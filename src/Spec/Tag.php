<?php

namespace WPOpenAPI\Spec;

class Tag
{
	private string $name;
	private string $description;

	public function __construct(string $name, string $description = '')
	{
		$this->name = $name;
		$this->description = $description;
	}

	public function getDescription(): string
	{
	    return $this->description;
	}

	public function setDescription($description)
	{
	    $this->description = $description;
	}

	public function toArray(): array
	{
	    return array(
			'name' => $this->name,
			'description' => $this->description
		);
	}
}
