    <h2 class="h4">Έλεγχος στοιχείων φοιτητή και φορέα</h2>

    <!-- <h2 class="h5">Έλεγχος μορφής</h2> -->
    <ul class="list-inline">
        @foreach ($checks as [$label, $status, $color])
            <li class="list-inline-item mb-1">
                <strong>{{ $label }}</strong>
                <span class="badge text-bg-{{ $color }}">{{ $status }}</span>
            </li>
        @endforeach
    </ul>

    @foreach ($groups as $source => $rows)
        <h2 class="h5 mt-4">{{ $source }}</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Πεδίο</th><th>Καταχωρισμένη τιμή</th><th>Τιμή από την πηγή</th><th>Αποτέλεσμα</th></tr></thead>
                <tbody>
                    @foreach ($rows as [$label, $local, $external, $status, $color])
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            <td class="text-break">{{ $local }}</td>
                            <td class="text-break">{{ $external }}</td>
                            <td><span class="badge text-bg-{{ $color }}">{{ $status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
