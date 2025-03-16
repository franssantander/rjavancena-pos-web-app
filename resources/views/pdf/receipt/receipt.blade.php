<!DOCTYPE html>
<html>

<head>
    <title>Receipt</title>
    <style>
        @page {
            margin: 0cm;
            /* Set margin for all edges to 0 */
        }

        html,
        body {
            margin: 0px;
            padding: 0px;
            font-family: Arial, sans-serif;
        }

        .container {
            padding: 10px;
            margin: 0 auto;
            width: 100%;
            /* Adjust the width to your needs */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            /* Example margin for the table */
        }

        th,
        td {
            padding: 5px;
            text-align: left;
            /* border: 1px solid #ddd; */
            /* Example border */
        }

        th {
            /* background-color: #f2f2f2; */
        }

        .total {
            font-weight: bold;
        }

        .header {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Rj Avacena</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 120px">Name</th>
                    <th style="width: 30px">Qty</th>
                    <th style="width: 50px">Price</th>
                    <th style="width: 50px">Total <br> Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($arr_purchases as $purchase)
                    <tr>
                        <td>{{ $purchase['name'] }}</td>
                        <td>{{ $purchase['qty'] }}</td>
                        <td>{{ number_format($purchase['discounted_price'] != 0.0 ? $purchase['discounted_price'] : $purchase['retail_price'], 2) }}
                        </td>
                        <td>{{ number_format($purchase['total_price'], 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="total"></td>
                    <td class="total" style="width: 50px">{{ number_format($final_total_price, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
