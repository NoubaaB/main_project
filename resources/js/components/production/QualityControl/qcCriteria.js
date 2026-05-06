/**
 * Fish freshness criteria definitions based on EU regulation 103/76.
 * Each criterion has 4 grade levels with observation descriptions.
 */
export const CRITERIA = [
  {
    key: 'skin_grade',
    icon: 'mdi-fish',
    descriptions: {
      extra: 'Bright, iridescent pigmentation; transparent mucus',
      a: 'Bright but not shiny; slightly cloudy mucus',
      b: 'Discoloring, dull; opaque mucus',
      non_admis: 'Dull skin; milky mucus',
    },
  },
  {
    key: 'eye_grade',
    icon: 'mdi-eye-outline',
    descriptions: {
      extra: 'Convex, transparent cornea; bright black pupil',
      a: 'Slightly sunken; slightly opalescent cornea',
      b: 'Flat eye; opaque pupil',
      non_admis: 'Concave; milky cornea; grey pupil',
    },
  },
  {
    key: 'gills_grade',
    icon: 'mdi-waves',
    descriptions: {
      extra: 'Bright color; no mucus',
      a: 'Less colored; slight clear mucus',
      b: 'Discoloring; opaque mucus',
      non_admis: 'Yellowish; milky mucus',
    },
  },
  {
    key: 'abdomen_grade',
    icon: 'mdi-stomach',
    descriptions: {
      extra: 'Bluish, translucent, shiny',
      a: 'Slightly altered, velvety',
      b: 'Slightly opaque',
      non_admis: 'Opaque',
    },
  },
  {
    key: 'backbone_grade',
    icon: 'mdi-bone',
    descriptions: {
      extra: 'Colorless',
      a: 'Slightly pink',
      b: 'Pink',
      non_admis: 'Red / brownish',
    },
  },
  {
    key: 'organs_grade',
    icon: 'mdi-heart-pulse',
    descriptions: {
      extra: 'Bright red organs and blood',
      a: 'Dull red, matte',
      b: 'Pale red / pale',
      non_admis: 'Brownish organs and blood',
    },
  },
  {
    key: 'texture_grade',
    icon: 'mdi-hand-back-right-outline',
    descriptions: {
      extra: 'Firm, elastic flesh',
      a: 'Less elastic',
      b: 'Slightly soft',
      non_admis: 'Flabby; scales detach easily',
    },
  },
  {
    key: 'odor_grade',
    icon: 'mdi-scent',
    descriptions: {
      extra: 'Seaweed / marine smell',
      a: 'Neutral, no bad smell',
      b: 'Slightly sour',
      non_admis: 'Putrid / sour odor',
    },
  },
];

export const GRADES = ['extra', 'a', 'b', 'non_admis'];

export const GRADE_SCORES = { extra: 3, a: 2, b: 1, non_admis: 0 };

export const GRADE_COLORS = {
  extra: '#2E7D32',
  a: '#1565C0',
  b: '#EF6C00',
  non_admis: '#C62828',
};

export const GRADE_ICONS = {
  extra: 'mdi-star-circle',
  a: 'mdi-check-circle',
  b: 'mdi-alert-circle',
  non_admis: 'mdi-close-circle',
};

export const DECISION_COLORS = {
  accepted_premium: '#2E7D32',
  accepted: '#1565C0',
  accepted_restricted: '#EF6C00',
  rejected: '#C62828',
};

export const APPROVAL_COLORS = {
  pending: '#F57F17',
  approved: '#2E7D32',
  rejected: '#C62828',
  needs_reinspection: '#EF6C00',
};

export function computeGrade(form) {
  const filled = CRITERIA.map(c => form[c.key]).filter(v => v != null);
  if (filled.length === 0) return { overall_grade: null, decision: null };
  const scores = filled.map(g => GRADE_SCORES[g]);
  const min = Math.min(...scores);
  const overall = min === 3 ? 'extra' : min === 2 ? 'a' : min === 1 ? 'b' : 'non_admis';
  const decision = { extra: 'accepted_premium', a: 'accepted', b: 'accepted_restricted', non_admis: 'rejected' }[overall];
  return { overall_grade: overall, decision };
}
