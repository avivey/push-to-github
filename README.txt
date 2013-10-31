need to patch libphutil with  https://secure.phabricator.com/differential/diff/16821/
and refresh github link.
phabricator needs https://secure.phabricator.com/D7466.

TODO:
  - link repo to GitHubProvider, account domain, add a "github repo name" field
  - Break pushable === hosted relation.
  - PhutilAuthAdapterOAuthGitHub::getScope depends on config.
  - UI for repo for those.
  - Consider repo policies
  - check that diff is accepted (in flow)
  - find committer name/email for github
  - alternative email address for commiter/author?
  - provision/maintain workspace copy.
  - async? makes for more UI.
  - UI in differential:
    - button is shown/available only when diff is accepted
    - all/most cases in "things that can go wrong" need UI.


