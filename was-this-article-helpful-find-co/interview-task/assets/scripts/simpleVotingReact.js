import ReactDOM from 'react-dom';
import React, { useEffect, useState } from 'react';

const SimpleVotingReact = ({ postId, hasVoted, userVote, yesVotes, noVotes, nonce }) => {
  const [percentageYes, setPercentageYes] = useState(0);
  const [percentageNo, setPercentageNo] = useState(0);
  const [voted, setVoted] = useState(hasVoted);
  const [userVoted, setUserVoted] = useState(userVote);

  const handleVote = (vote) => {
    if (!voted) {
      // Disable buttons
      setVoted(true);
      setUserVoted(vote);

      // Sanitize input and use encodeURIComponent for security
      const sanitizedPostId = encodeURIComponent(postId);
      const sanitizedVote = encodeURIComponent(vote);

      // Send Ajax request
      fetch(simple_voting_ajax.ajax_url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=simple_voting&post_id=${sanitizedPostId}&vote=${sanitizedVote}&security=${nonce}`,
      })
        .then((response) => response.json())
        .then((data) => {
          // Display results
          setPercentageYes(Math.round(data.percentage_yes));
          setPercentageNo(Math.round(data.percentage_no));
        });
    }
  };

  useEffect(() => {
    // Calculate percentages based on initial data
    const totalVotes = yesVotes + noVotes;
    setPercentageYes(totalVotes > 0 ? Math.round((yesVotes / totalVotes) * 100) : 0);
    setPercentageNo(totalVotes > 0 ? Math.round((noVotes / totalVotes) * 100) : 0);
  }, [yesVotes, noVotes]);

  return (
    <div className="findco-simple-voting" data-post-id={postId}>
        <p className="findco-simple-voting__text">
            {voted ? simple_voting_translations.thank_you_feedback : simple_voting_translations.was_article_helpful}
        </p>
        <button id="simple-vote-yes" className="findco-simple-voting__button findco-simple-voting__button--yes" data-voted={!!userVoted} onClick={() => handleVote(1)} disabled={voted}>
            {voted ? `${percentageYes}%` : 'Yes'}
        </button>
        <button id="simple-vote-no" className="findco-simple-voting__button findco-simple-voting__button--no" data-voted={!userVoted} onClick={() => handleVote(0)} disabled={voted}>
            {voted ? `${percentageNo}%` : 'No'}
        </button>
    </div>
  );
};

const rootElement = document.querySelector('#simple-voting-react-root');
if (rootElement) {
    ReactDOM.render(
        React.createElement(SimpleVotingReact, {
            postId: parseInt(simple_voting_ajax.post_id),
            hasVoted: simple_voting_ajax.has_voted,
            userVote: simple_voting_ajax.user_vote,
            yesVotes: parseInt(simple_voting_ajax.yes_votes),
            noVotes: parseInt(simple_voting_ajax.no_votes),
            nonce: simple_voting_ajax.nonce,
        }),
        rootElement
    );
}

export default SimpleVotingReact;
