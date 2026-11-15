# Versioning and GitHub Releases

The footer gets its version from `includes/version.php`. The latest published GitHub Release is **v2.5.1**. The Growthtopia rebrand is being prepared locally as **v2.5.2** and is not yet committed or released. A commit is not a GitHub Release: Releases appear on the repository's Releases page only after you create and publish a release for a version tag.

Create one GitHub Release for each finished, tested version—not for every individual commit. Review and commit the prepared v2.5.2 changes first. Use patch versions for future updates: **v2.5.3**, **v2.5.4**, and so on. See the [release notes](../../CHANGELOG.md).

## Create and publish a version

Do the following from the repository root when a set of changes is complete and tested:

1. For the prepared v2.5.2 release, the app version and notes are already updated locally. For later releases, update `APP_VERSION` in `agrinavarro/includes/version.php`, the current app version in both READMEs, and add a section to [CHANGELOG.md](../../CHANGELOG.md). Write the actual additions, changes, fixes, and checks performed. Use `v2.5.3` next, then increment the patch number for each finished release.
2. Review and test the changes. Check that local credentials and private data are not included:

   ```powershell
   git status --short
   git diff --check
   git diff
   git diff --cached
   git check-ignore agrinavarro\includes\db.local.php agrinavarro\.env
   git ls-files agrinavarro\includes\db.local.php agrinavarro\.env
   ```

   The ignore check should list local configuration files, while `git ls-files` should print nothing.
3. Stage and review only the intended files, then create the version commit:

   ```powershell
   git add <files-you-reviewed>
   git diff --cached --check
   git diff --cached
   git commit -m "Release Growthtopia v2.5.2"
   git push origin main
   ```

   Run `git add` with the actual file paths instead of the example placeholder. Do not use `git add -f` on credentials.
4. Create and push the matching annotated version tag:

   ```powershell
   git tag -a v2.5.2 -m "Growthtopia v2.5.2"
   git push origin v2.5.2
   ```

5. On GitHub, open the repository's **Releases** page, select **Draft a new release**, choose the existing `v2.5.2` tag, set the release title to `Growthtopia v2.5.2`, and paste that version's notes from `CHANGELOG.md` into the description. Select **Publish release**.

For this prepared release use `v2.5.2`. For the next finished version, use `v2.5.3`. A commit or tag alone will not create an entry on the Releases page. Do not publish a release until the code and notes match and the tests listed in the notes have actually passed.

## Repository About details

On the repository page, use **Edit repository details** and enter:

- **Repository name:** `GrowthTopia` (the repository has already been renamed on GitHub).
- **Description:** `Growthtopia is a PHP and Supabase PostgreSQL Agriculture Information System for managing farmers, farms, crops, plantings, harvests, reports, and account roles. Built as a BSIS school project.`
- **Website:** The previous Render URL `https://datacorepoint.onrender.com/login.php` returned HTTP 503 on 2026-11-15. Keep it only if the Render service is restored; otherwise update or clear the Website field.
- **Topics:** `php`, `supabase`, `postgresql`, `agriculture`, `information-system`, `school-project`

GitHub contributor attribution follows commit authorship and email association. Make commits with your own Git identity and an email linked to your GitHub account; do not add a Copilot co-author trailer. The contributors graph can take time to refresh.

### Release description template

Copy this structure into the matching `CHANGELOG.md` version section and into the GitHub Release description. Remove headings that do not apply and describe only changes included in that version.

```markdown
## What's updated
- Describe the user-visible changes.

## Fixes
- Describe bugs fixed.

## Verification
- List the tests actually run and their results.
```

Never include passwords, secrets, or real personal data in release notes. If credentials were committed in the past, rotate them; deleting them later does not remove them from Git history.

## Roll back

Inspect a tagged release without changing the active branch:

```powershell
git switch --detach v2.2
```

Return to the working branch with `git switch main`. To undo a change already on the branch while preserving history, use `git revert <commit-id>`, test the result, and commit and push the revert. Avoid `git reset --hard` for routine rollback because it discards local work.

## Hosting

The supplied Render Docker image uses PHP 8.3, Apache, and `pdo_pgsql`. See [the Render deployment guide](DEPLOYMENT.md). The previously listed Render URL returned HTTP 503 on 2026-11-15 and should be checked in the Render Dashboard before sharing.
