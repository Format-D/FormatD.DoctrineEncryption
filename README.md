
# FormatD.DoctrineEncryption

This Neos Flow package lets you encrypt your persisted data in the database.

## What does it do?

It hooks into the doctrine persistence layer and encrypts data when it is written to the database. The data is decrypted again when the models are loaded from the database.

The package is inspired by several packages like this for other frameworks or directly for doctrine.

## Kompatiblität

Versioning scheme:

     1.0.0 
     | | |
     | | Bugfix Releases (non breaking)
     | Neos Compatibility Releases (non breaking except framework dependencies)
     Feature Releases (breaking)

Releases und compatibility:

| Package-Version | Neos Flow Version      |
|-----------------|------------------------|
| 1.0.x           | >= 5.x                 |

## Setup

Firstly set your private encryption key in the configuration. (If the encryption key is lost your data in the database is lost too)

```
FormatD:
  DoctrineEncryption:
    secretKey: '<PleaseSetYourSecretKey>'
```

## Make database columns encrypted

There are two ways to configure encrypted database columns. Either via annotation in the model itself or by configuration in the Settings.yaml.

### Configure with Annotation

You can use `text_encrypted` or `array_encrypted` as column type.

Example:
```

	/**
	 * @var string
	 * @ORM\Column(type="text_encrypted")
	 */
	protected $myConfidentialProperty = '';
	
```

### Configure in Settings

If you want to entrypt data from another package you can do this by adding configuration. Currently only `method: default` is supported.

Example:
```
FormatD:
  DoctrineEncryption:
    entities:
      MyPackage\Website\Domain\Model\User:
        phoneNumber:
          method: default
        name.firstName:
          method: default
        name.lastName:
          method: default
        name.fullName:
          method: default
        primaryElectronicAddress.identifier:
          method: default
```

