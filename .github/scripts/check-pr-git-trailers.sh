#!/usr/bin/env bash
# Requires: git, jq, and curl
set -euo pipefail

# jq's `// ""` substitutes an empty string if the `.pull_request.body` is null (or false)
# https://jqlang.org/manual/#alternative-operator
pr_description=$(jq -r '.pull_request.body // ""' "${GITHUB_EVENT_PATH}")
# A PR description is not a patch so ignore any Markdown dividers (---)
git_trailers=$(printf '%s\n' "${pr_description}" | git interpret-trailers --parse --no-divider)
# -x requires a whole-line match
if grep -x 'Bug: T[1-9][0-9]*' <<< "${git_trailers}" > /dev/null; then
    echo 'Found a Git trailer for a likely Phabricator task.'
    exit 0
fi

# Wikibase Cloud (Kanban Board)
# https://phabricator.wikimedia.org/project/board/8048/
# PHID found via:
#   curl https://phabricator.wikimedia.org/api/project.search \
#     -d api.token=<api-token> \
#     -d constraints[ids][0]=8048
project_PHID='PHID-PROJ-pmh2lan2qqkhxh43p66x'
# Waiting for Peer Review column
# https://phabricator.wikimedia.org/project/board/8048/column/35965/
# PHID found via:
#   curl https://phabricator.wikimedia.org/api/project.column.search \
#     -d api.token=api-token \
#     -d constraints[ids][0]=35965
column_PHID='PHID-PCOL-3zqq4vuit77cj3l4pl7y'

# URL encode directly from the event to preserve special characters and newlines
task_url=$(jq -r --arg project_PHID "${project_PHID}" --arg column_PHID "${column_PHID}" '
    .pull_request as $pr |
    "https://phabricator.wikimedia.org/maniphest/task/edit/form/1" +
    "?project=\($project_PHID)&column=\($column_PHID)" +
    "&title=" + ($pr.title | @uri) +
    "&description=" + ((($pr.body // "") + "\n\n---\n\n**PRs**:\n- [] \($pr.html_url)") | @uri)
' "${GITHUB_EVENT_PATH}")

author=$(jq -r '.pull_request.user.login' "${GITHUB_EVENT_PATH}")
pr_comment=$(cat <<EOF
@${author}, please add any related Phabricator tasks as git trailers at the end of the PR description, separated from the text by a blank line.

For example (replace the example task IDs with your related tasks):

\`\`\`text
Example PR description text

Bug: T12345
Bug: T67890
\`\`\`

You can also create a new task with the same title and description as this PR using the link below, then add its task ID as a \`Bug:\` trailer:

- [Create task in the "Waiting for Peer Review" column](${task_url})
EOF
)

pr_number=$(jq -r '.pull_request.number' "${GITHUB_EVENT_PATH}")
# --silent hides the progress meter; --show-error keeps diagnostics on stderr.
# --fail-with-body returns a failure status for HTTP errors while writing the body to stdout.
jq -n --arg body "${pr_comment}" '{body: $body}' |
    curl --silent --show-error --fail-with-body --max-time 30 \
        --request POST \
        --header "Authorization: Bearer ${GH_TOKEN}" \
        --header 'Accept: application/vnd.github+json' \
        --header 'Content-Type: application/json' \
        --data-binary @- \
        "${GITHUB_API_URL}/repos/${GITHUB_REPOSITORY}/issues/${pr_number}/comments"

echo 'Created reminder comment on the pull request.'
