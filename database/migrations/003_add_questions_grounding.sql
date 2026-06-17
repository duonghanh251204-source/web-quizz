ALTER TABLE questions
  ADD COLUMN evidence_quote LONGTEXT NULL AFTER correct_answer,
  ADD COLUMN reasoning LONGTEXT NULL AFTER evidence_quote,
  ADD COLUMN explanation LONGTEXT NULL AFTER reasoning,
  ADD COLUMN confidence_score TINYINT UNSIGNED NULL AFTER explanation,
  ADD COLUMN grounding_status VARCHAR(20) NULL DEFAULT 'unknown' AFTER confidence_score;
