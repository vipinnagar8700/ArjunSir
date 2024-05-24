
# Terminal Action Query

## Structure

`TerminalActionQuery`

## Fields

| Name | Type | Tags | Description | Getter | Setter |
|  --- | --- | --- | --- | --- | --- |
| `filter` | [`?TerminalActionQueryFilter`](../../doc/models/terminal-action-query-filter.md) | Optional | - | getFilter(): ?TerminalActionQueryFilter | setFilter(?TerminalActionQueryFilter filter): void |
| `sort` | [`?TerminalActionQuerySort`](../../doc/models/terminal-action-query-sort.md) | Optional | - | getSort(): ?TerminalActionQuerySort | setSort(?TerminalActionQuerySort sort): void |

## Example (as JSON)

```json
{
  "include": [
    "CUSTOMER"
  ],
  "limit": 2,
  "query": {
    "filter": {
      "status": "COMPLETED"
    }
  },
  "filter": {
    "device_id": "device_id0",
    "created_at": {
      "start_at": "start_at4",
      "end_at": "end_at8"
    },
    "status": "status6",
    "type": "SAVE_CARD"
  },
  "sort": {
    "sort_order": "DESC"
  }
}
```

