# Composer Patch Manager
The aim of this plugin is to be able to easily generate and apply patches in conjunction with composer.

Any changes already made to a package can be saved to a patch file, and then re-applied either manually or automatically when the package is updated or reinstalled.

## Difference to [composer-patches](https://github.com/cweagans/composer-patches)?
**composer-patches** is a great plugin if you want to simply apply _existing_ patches to the _same_ version of a package.

CPM will both generate these patches for you if desired, but also will heuristically make more efforts to apply the patch if a new version has changed the code and it struggles to apply initially.

The generated patches and `composer-patches.json` file are both compatible with **composer-patches**, so it is easy to transition to/from either plugin. Please note that at the moment CPM only supports _local_ patches.

## Requirements

This plugin requires that you have `git`, `php`, and `composer` installed, and the PHP `exec()` function is enabled on the CLI.

If you are on a server and any of these are not available and cannot be installed it is advisable to use this plugin *locally* and upload any changes.

## Installation
`composer require gwsmedia/composer-patch-manager`

If you would like CPM to automatically apply patches the following command will add the necessary hooks:
`vendor/bin/cpm init`

If you would like to _only_ apply patches on install or only on update, after running the command above, within `composer.json` remove the ComposerPatchManager hook in `post-package-install` or `post-package-update`, respectively.

## Usage
### Generating patches
Make any necessary changes to packages installed with composer (in `vendor/` or at the destination defined in `installer-paths` in your `composer.json`).

Then define the packages you would like to generate patches for in `composer-patches.json`:
```
{
	"packages": ["vendor/package"]
}
```

When ready, run `vendor/bin/cpm generate`.

CPM will run through each defined package, output a patch file to `patch/vendor--package.patch`, and update `composer-patches.json` with a path to this file.

All changes are captured collectively in a singular patch file for each package, so feel free to make amendments and then regenerate.

### Applying patches
Any patch files defined in `composer-patches.json` will be applied. If generated with the command above, they will automatically be added to the config file, but other patches can be added as well:
```
{
	"patches": {
		"vendor/project": {
			"Patch title: "patch/custom.patch"
		}
	}
}
```

Now, if you ran `vendor/bin/cpm init`, CPM will attempt to apply these patches whenever the relevant package is installed or updated.

If you would like to do a one time run use:
`vendor/bin/cpm apply`.

#### Patching process

CPM uses the following heuristic methodology for applying patches (using `git diff --no-index`):

1. Attempt to apply patch normally
2. Then, attempt with `--recount` (ignoring line numbers)
3. Then, attempt with `-C1` (only using 1 line of context either side)
4. Finally, if still failing, attempt with `--reject` which will apply any hunks in a patch it can, and failed hunks will be stored in `.rej` files.
    - CPM will run a **dry run** of this command and ask the user if they would like to proceed before making the changes.
    - It will then show the user a list of all the rejected hunks.
    - **Please note:** if you proceed with this method, not all of the changes in the patch file will be applied, so make sure no code within the patch relies on the rejected hunks. If so, it is best to do this *locally*, apply the rejected hunks manually, test, and then upload.

## Example composer-patches.json
```
{
	"packages": ["psr/container", "drupal/quiz"],
	"patches": {
		"psr/container": {
			["patch/psr--container.patch"]
		},
		"drupal/quiz": {
			[
				"patch/drupal--quiz.patch",
				"patch/external.patch"
			]
		}
	}
}
```

## Known issues
- At the moment, you must only run `/vendor/bin/cpm` commands in the root project directory (where composer.json and composer.lock are defined)
- `repositories` defined in composer.json that use `type: path` and a relative path will break the `generate` command
- All patches must be relative to the project root - this is not ideal for patches downloaded from drupal.org for example which will be relative to the package folder, but this can be worked around by applying the patch manually, and then running the `generate` command to include it in the collective patch file.