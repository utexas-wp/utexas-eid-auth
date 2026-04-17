---
name: Release checklist
about: Instructions for packaging a release of utexas-eid-auth

---

This workflow assumes a "regular release," meaning there are changes already committed to the `develop` branch that are ready to be included in a tagged release.

- [ ] Stage a release branch and pull request using the [Release Stager](https://github.com/utexas-wcms/wcms-devops/actions/workflows/regular-release-stager.yml).
- [ ] Confirm that the staged pull request correctly increments the version number defined in `utexas-eid-auth.php`
- [ ] Merge the release using [Release Deployer](https://github.com/utexas-wcms/wcms-devops/actions/workflows/regular-release-deployer.yml). This will create a new tag.
- [ ] Confirm that the tag is correctly synced to the public mirror repository at https://github.com/utexas-wp/utexas-eid-auth/tags . That sync is the responsibility of the `public-github-mirror-sync` GitHub Action in this repository.
- [ ] Download the latest version of the plugin at https://github.com/utexas-wp/utexas-eid-auth/archive/refs/heads/master.zip . This will download a file named `utexas-eid-auth-master.zip`
- [ ] Unzip `utexas-eid-auth-master.zip`, rename the decompressed directory from `utexas-eid-auth-master` to `utexas-eid-auth` and compress in zip format so that the resulting file is `utexas-eid-auth.zip`. **It is important that you do not simply rename the .zip file you downloaded, as this will not change the directory contained in it. We need the directory to be named `utexas-eid-auth` so that the WordPress UI plugin updater installs the plugin consistently to `wp-content/plugins/utexas-eid-auth`, rather than `wp-content/plugins/utexas-eid-auth-master`.**
- [ ] Sign into public GitHub with an account that has administrative access to https://github.com/utexas-wp/utexas-eid-auth
- [ ] Create a new release pointing to the new tag at https://github.com/utexas-wp/utexas-eid-auth/releases/new . Use previous releases as the model for the text of the changelog. **Finally, add the `utexas-eid-auth.zip` file as asset where it says "Attach binaries by dropping them here or selecting them."
- [ ] Confirm that the contents of the latest release asset is available via our download endpoint at https://wcms.its.utexas.edu/download-utexas-eid-auth.php by unzip the download and confirming that the `utexas-eid-auth.php` file has the latest version number in its metadata.
