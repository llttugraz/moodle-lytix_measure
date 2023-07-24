# lytix\_measure

This visualises the students’ scores. Teachers see the lowest, the highest and the average score; students see their own score instead of the lowest score.


## Inner Workings

This widget draws three arcs with decreasing radius. The length of each arc is determined by its radius and the `GAP_FACTOR`, which determines the length relative to a circle; a value of `0.25` produces an arc with a gap of a quarter circle.

All three arcs have the same length relative to their radius.

To render the fetched data a dashed stroke is being used: `stroke-dasharray` of each arc is set to its full length, the score is represented by using `stroke-dashoffset`.

In order to calculate the scores we rely heavily on shared indexes (and therefore on the magic number `3`, for the three values that are shown).

The needle has some hardcoded parameters that could be solved more elegantly.


## JSON

```
{
	Name: <string>, // name of the student
	StudentCount: <float>,
	ActivityCount: {
		Past: <float>,
		Future: <float>
	},
	// The values are connected by index.
	// The 'total' values must always be on index 0, other values can be chosen freely;
	// in this case the values for 'quiz' are on index 1, and so on.
	Scores: {
		Activity: [ 'total', 'quiz', … ],
		Mine: [ <float>, … ],
		Lowest: [ <float>, … ],
		Highest: [ <float>, … ],
		Avg: [ <float>, … ],
		Max: [ <float>, … ] // maximum points that can be achieved
	}
}
```

### Example

```js
const testData = {
    Name: 'Echtgeiler Name', // Name of the student
    StudentCount: Math.floor(Math.random() * 999),
    ActivityCount: {
        Past: Math.floor(Math.random() * 64),
        Future: Math.floor(Math.random() * 64),
    },
    // The values are connected by index.
    // In this case, the 'total' values are always on index 0,
    // the values for 'quiz' are on index 1, and so on.
    Scores: {
        Activity: ['total', 'quiz', 'forum'],
        Mine: [120, 50, 70],
        Lowest: [80, 40, 40],
        Highest: [130, 70, 70],
        Avg: [100, 55, 63],
        Max: [150, 70, 70], // Maximum points that can be achieved
    },
};
```
